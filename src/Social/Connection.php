<?php
/**
 * Base connection class.
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * Base connection.
 */
abstract class Connection
{
    /**
     * Default options for curl.
     * 
     * @var array
     */
    protected $curl_opts = array(
        CURLOPT_CONNECTTIMEOUT      => 10,
        CURLOPT_RETURNTRANSFER      => true,
        CURLOPT_TIMEOUT             => 60,
        CURLOPT_USERAGENT           => 'JasnySocial/1.0',
        CURLOPT_HTTPHEADER          => array('Expect:'),
        CURLOPT_FOLLOWLOCATION      => true,
        CURLOPT_MAXREDIRS           => 3,
        CURLOPT_TIMEOUT             => 10,
    );
    
    
    /**
     * Errors from doing a multirequest
     * @var array
     */
    private $multiRequestErrors = null;

    
    /**
     * Get API base URL.
     * {{ @internal Should end with a slash }
     * 
     * @param string $url  Relative URL
     * @return string
     */
    abstract protected function getBaseUrl($url=null);

    /**
     * Get full URL.
     * 
     * @param string $url     Relative or absolute URL or a request object
     * @param array  $params  Parameters
     */
    public function getUrl($url=null, array $params=array())
    {
        if (is_object($url)) {
            if (isset($url->params)) $params = $url->params + $params;
            $url = $url->url;
        }
        
        if (strpos($url, '://') === false) $url = $this->getBaseUrl($url) . ltrim($url, '/');
        return $this->buildUrl($url, $params);
    }
    
    
    /**
     * Get errors from the last muli request call.
     * 
     * @return array
     */
    public function getMultiRequestErrors()
    {
        return $this->multiRequestErrors;
    }
    
    
    /**
     * Do an HTTP request.
     * 
     * @param string   $method         GET, POST or DELETE
     * @param string   $url
     * @param array    $params         REQUEST parameters
     * @param array    $headers        Additional HTTP headers
     * @param callback $writefunction  Stream content to this function, instead of returning it as result
     * @return string
     */
    protected function httpRequest($method, $url, $params=null, array $headers=array(), $writefunction=null)
    {
        $ch = $this->curlInit($method, $url, $params, $headers);
        
        if (isset($writefunction)) {
            curl_setopt($ch, CURLOPT_TIMEOUT, 0); // Don't timeout
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, $writefunction);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        }
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false || $httpcode >= 300) throw new Exception("HTTP $method request for '" . $this->getUrl($url) . "' failed: " . ($result === false ? $error : $this->httpError($httpcode, $result)));

        return $result;
    }
    
    /**
     * Run multiple HTTP requests in parallel.
     * 
     * Note this function will not throw an exception if requests, instead you can retrieve errors using `getMultiRequestErrors()`.
     * 
     * @param array $requests  Array of value objects { 'method': string, 'url': string, 'params': array, 'headers': array }
     * @return array
     */
    public function multiRequest(array $requests)
    {
        $results = array();
        $this->multiRequestErrors = array();
        
        $handles = array();
        $mh = curl_multi_init();
        
        foreach ($requests as $key=>$request) {
            $ch = $this->curlInit(isset($request->method) ? $request->method : 'GET', $result->url, isset($request->params) ? $request->params : array(), isset($request->headers) ? $request->headers : array());
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }
        
        $active = null; 
        do { 
            $status = curl_multi_exec($mh, $active);
            if (curl_multi_select($mh, $this->curl_opts[CURLOPT_TIMEOUT]) < 0) { // pause the loop until somethings happens
                break; // timeout
            }
        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);
        
        foreach ($handles as $key=>$ch) {
            $result = curl_multi_getcontent($ch);
            $error = curl_error($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            curl_multi_remove_handle($mh, $ch);

            if ($result === false || $httpcode >= 300) {
                $this->multiRequestErrors[$key] = "HTTP $method request for '" . $this->getUrl($url) . "' failed: " . ($result === false ? $error : $this->httpError($httpcode, $result));
            } else {
                $results[$key] = $result;
            }
        }
        
        curl_multi_close($mh);
        return $results;
    }
    
    /**
     * Initialize a cURL session.
     * 
     * @param string $method   GET, POST or DELETE
     * @param string $url
     * @param array  $params   REQUEST parameters
     * @param array  $headers  Additional HTTP headers
     * @return 
     */
    private function curlInit($method, $url, $params=null, array $headers=array())
    {
        $url = $this->getUrl($url, $method != 'POST' ? $params : array());

        $ch = curl_init($url);
        curl_setopt_array($ch, $this->curl_opts);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            if (!isset($headers['Content-Type']) || $headers['Content-Type'] != 'multipart/form-data') $params = self::buildHttpQuery($params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        if ($headers) {
            foreach ($headers as $key=>$value) {
                if (is_int($key)) continue;
                unset($headers[$key]);
                $headers[] = "$key: $value";
            }
            
            if (isset($this->curl_opts[CURLOPT_HTTPHEADER])) $headers = array_merge($this->curl_opts[CURLOPT_HTTPHEADER], $headers);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        return $ch;
    }
    
    /**
     * Get error from HTTP result.
     * 
     * @param int    $httpcode
     * @param string $result
     * @return string
     */
    private function httpError($httpcode, $result)
    {
        $data = json_decode($result);
        
        // Not JSON
        if (!isset($data)) {
            if (preg_match('/<html/i', $result) || $result == '') return $httpcode;
            return $result;
        }
        
        // JSON
        if (isset($data->error)) return is_scalar($data->error) ? $data->error : $data->error->message;
          elseif (isset($data->errors)) return is_scalar($data->errors[0]) ? $data->errors[0] : $data->errors[0]->message;
          elseif (isset($data->error_msg)) return $data->error_msg;
        
        return $result; // Return the JSON as string (this shouldn't happen)
    }
    
    
    /**
     * Get the URL of the current script.
     *
     * @param string $page    Relative path to page
     * @param array  $params
     * @return string
     */
    static public function getCurrentUrl($page=null, array $params=array())
    {
        if (strpos($page, '://') !== false) return self::buildUrl($page, $params);
        
        if (!isset($_SERVER['HTTP_HOST'])) return null;

        if (!isset($page)) $page = $_SERVER['REQUEST_URI'];
        if ($page[0] != '/') $page = dirname($_SERVER['REQUEST_URI']) . '/' . $page;
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
        $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $page;
        
        return self::buildUrl($currentUrl, $params);
    }
    
    /**
     * Build a url, setting parameters.
     * 
     * @param string  $url
     * @param array   $params
     * @param boolean $overwrite  Overwrite existing parameters
     */
    static protected function buildUrl($url, array $params, $overwrite=true)
    {
        if (empty($params)) return $url;
        
        $parts = parse_url($url);

        if (isset($parts['query'])) {
            $query_params = array();
            parse_str($parts['query'], $query_params);

            if ($overwrite) $params = $params += $query_params;
             else $params = $query_params + $params;
        }

        $query = self::buildHttpQuery($params);

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . $parts['path'] . ($query ? '?' . $query : '');
    }
    
    /**
     * Build a HTTP query, converting arrays to a comma seperated list and removing null parameters.
     * 
     * @param type $params
     * @return string
     */
    static protected function buildHttpQuery($params)
    {
        foreach ($params as $key=>&$value) {
            if (!isset($value)) {
                unset($params[$key]);
                continue;
            }

            if (is_array($value)) $value = join(',', $value);
            $value = rawurlencode($key) . '=' . rawurlencode($value);
        }
       
        return join('&', $params);
    }

    /**
     * Parse URL and return parameters
     * 
     * @param string $url
     * @return array
     */
    static protected function extractParams($url)
    {
        $params = array();

        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) parse_str($query, $params);
        
        return $params;
    }
    

    /**
     * Fetch from web service.
     * 
     * @param string  $resource
     * @param array   $params
     * @param boolean $convert   Convert to entity/collection, false returns raw data
     * @return Entity|Collection|mixed
     */
    abstract public function get($resource, array $params=array(), $convert=true);
    
    /**
     * Post to web service.
     * 
     * @param string  $resource
     * @param array   $params    POST parameters
     * @param boolean $convert   Convert to entity/collection, false returns raw data
     * @return Entity|Collection|mixed
     */
    abstract public function post($resource, array $params=array(), $convert=true);
    
    
    /**
     * Convert data to Entity, Collection or DateTime.
     * 
     * @param mixed   $data
     * @param string  $type    Entity type
     * @param boolean $stub    If an Entity, asume it's a stub
     * @param object  $source  { 'url': string, 'params': array }
     * @return Entity|Collection|DateTime|mixed
     */
    abstract public function convertData($data, $type=null, $stub=true, $source=null);
}
