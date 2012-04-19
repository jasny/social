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
    protected static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT      => 10,
        CURLOPT_RETURNTRANSFER      => true,
        CURLOPT_TIMEOUT             => 60,
        CURLOPT_USERAGENT           => 'jasny-social-1.0',
        CURLOPT_HTTPHEADER          => array('Content-Type:', 'Content-Length:', 'Expect:'),
        CURLOPT_FOLLOWLOCATION      => true,
        CURLOPT_MAXREDIRS           => 3,
        CURLOPT_TIMEOUT             => 10,
    );
    
    /**
     * Get API base URL.
     * {{ @internal Should end with a slash }
     * 
     * @return string
     */
    abstract protected function getBaseUrl();

    /**
     * Get full URL.
     * 
     * @param string $path 
     * @param array  $params  Parameters
     */
    public function getUrl($path=null, array $params=array())
    {
        $url = strpos($path, '://') === false ? $this->getBaseUrl() . ltrim($path, '/') : $path;
        return self::buildUrl($url, $params);
    }
    
    /**
     * Do an HTTP request.
     * 
     * @param string $method   GET, POST or DELETE
     * @param string $url
     * @param array  $params   REQUEST parameters
     * @param array  $headers  Additional HTTP headers
     * @return string
     */
    protected function httpRequest($method, $url, $params=null, array $headers=array())
    {
        $ch = $this->curlInit($method, $url);
        
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
     * @param array $requests  Array of value objects { 'method': string, 'url': string, 'params': array, 'headers': array }
     * @return array
     */
    public function multiRequest(array $requests)
    {
        $handles = array();
        $mh = curl_multi_init();
        
        foreach ($requests as $request) {
            $ch = $this->curlInit($request->method, $result->url, isset($request->params) ? $request->params : array(), isset($request->headers) ? $request->headers : array());
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }
        
        $active = null; 
        do { 
            $status = curl_multi_exec($mh, $active);
            if (curl_multi_select($mh, self::$CURL_OPTS[CURLOPT_TIMEOUT]) < 0) { // pause the loop until somethings happens
                break; // timeout
            }
        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);
        
        foreach ($handles as $ch) {
            $result = curl_multi_getcontent($ch);
            $error = curl_error($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            curl_multi_remove_handle($mh, $ch);

            if ($result === false || $httpcode >= 300) {
                $results[] = new Exception("HTTP $method request for '" . $this->getUrl($url) . "' failed: " . ($result === false ? $error : $this->httpError($httpcode, $result)));
            } else {
                $results[] = $result;
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
        curl_setopt_array($ch, static::$CURL_OPTS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($method == 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        if ($headers) {
            foreach ($headers as $key=>$value) {
                if (is_int($key)) continue;
                unset($headers[$key]);
                $headers[] = "$key: $value";
            }
            if (isset(static::$CURL_OPTS[CURLOPT_HTTPHEADER])) $headers = array_merge($headers, static::$CURL_OPTS[CURLOPT_HTTPHEADER]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
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
            if (preg_match('/<html/i', $result)) return $httpcode;
            return $result;
        }
        
        // JSON
        if (isset($data->error)) return $data->error->message;
          elseif (isset($data->errors)) return $data->errors[0]->message;
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

        foreach ($params as $key=>&$value) {
            if (!isset($value)) unset($params[$key]);
            if (is_array($value)) $value = join(',', $value);
        }
        $query = !empty($params) ? '?' . http_build_query($params, null, '&') : '';

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . $parts['path'] . $query;
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
     * Fetch raw data from API.
     * 
     * @param string $resource
     * @param array  $params  Get parameters
     * @return array
     */
    abstract public function getData($resource, array $params=array());
    
    /**
     * Fetch an entity (or other data) from API.
     * 
     * @param string $resource
     * @param array  $params
     * @return Entity
     */
    abstract public function get($resource, array $params=array());
    
    
    /**
     * Create a new entity
     * 
     * @param string $type
     * @param array  $data
     * @return Entity
     */
    abstract public function create($type, $data=array());
    
    /**
     * Create a new collection.
     * 
     * @param string $type       Type of entities in the collection
     * @param array  $data
     * @param string $nextPage
     * @return Collection
     */
    abstract public function collection($type, $data=array(), $nextPage=null);
    
    /**
     * Create a stub.
     * 
     * @param string       $type
     * @param array|string $data  Data or id
     * @return Entity
     */
    abstract public function stub($type, $data);

    
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
