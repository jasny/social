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
     * Prepared requests stack
     * @var $array
     */
    private $prepared;
    
    /**
     * Use $_SESSION for authentication
     * @var boolean
     */
    protected $authUseSession = false;

    
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

        $url = $this->processPlaceholders($url, $params);
        
        if (strpos($url, '://') === false) $url = $this->getBaseUrl($url) . ltrim($url, '/');
        return $this->buildUrl($url, $params);
    }
    
    /**
     * Replace placeholders with data
     * 
     * @param string $url     Relative or absolute URL or a request object
     * @param array  $params  Parameters
     */
    protected function processPlaceholders($url, &$params)
    {
        if (strpos($url, '/:') === false) return $url;
        
        foreach ($params as $key=>$value) {
            if ($key[0] == ':') {
                $url = str_replace($key, $value, $url);
                unset($params[$key]);
            }
        }
        
        return $url;
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
     * Prepare and buffer all requests.
     * Calling execute() will send all buffered requests.
     * 
     * Request buffers are stackable, that is, you may call prepare() while another prepare() is active. Just make sure
     * that you call execute() the appropriate number of times.
     * 
     * @param Collection $target  Call $target->setData($results) after execute.
     */
    public function prepare($target=null)
    {
        $prepared = (object)array('target'=>$target, 'requests'=>array(), 'parent'=>$this->prepared);

        if ($this->prepared) $this->prepared->requests[] = $prepared;
        $this->prepared = $prepared;
    }
    
    /**
     * Execute buffered requests.
     * 
     * @return array|null
     */
    public function execute()
    {
        if ($this->prepared->parent) {
            $this->prepared = $this->prepared->parent;
            return null;
        }
        
        $prepared = $this->prepared;
        $this->prepared = null;
        
        $requests = $this->getPreparedRequests($prepared);
        $results = $this->doMultiRequest($requests);
        return $this->handlePrepared($prepared, $requests, $results);
    }
    
    /**
     * Add a request to the prepare buffer.
     * 
     * @param object $request
     */
    protected function addPreparedRequest($request)
    {
        $this->prepared->requests[] = $request;
    }


    /**
     * Get all requests from prepare buffer.
     * 
     * @param array $prepared  Prepared buffer
     * @return array
     */
    protected function getPreparedRequests($prepared)
    {
        $requests = array();
        
        foreach ($prepared->requests as $request) {
            if (isset($request->requests)) $requests += $this->getPreparedRequests($request);
              else $request[] = $request;
        }
        
        return $requests;
    }
    
    /**
     * Process results from prepared requests.
     * 
     * @param array $prepared
     * @param array $requests
     * @param array $results
     * @return array|Collection|Result
     */
    protected function handlePrepared($prepared, $requests, $results)
    {
        $ret = array();
        
        foreach ($prepared->requests as $i=>$request) {
            if (isset($request->requests)) {
                $ret[$i] = $this->handlePrepared($request, $requests, $results);
            } else {
                $key = array_search($request, $requests, true);
                if (isset($results[$key])) $ret[$i] = $results[$key];
            }
        }
        
        if ($prepared->target) $ret = $prepared->target->setData($ret);
        return $ret;
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
        $contenttype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($error || $httpcode >= 300) {
            throw new Exception("HTTP $method request for '" . $this->getUrl($url) . "' failed: " . ($error ?: $this->httpError($httpcode, $contenttype, $result)));
        }
        
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
    protected function httpMultiRequest(array $requests)
    {
        $results = array();
        $this->multiRequestErrors = array();
        
        // prepare requests and handles
        $handles = array();
        $mh = curl_multi_init();
        
        foreach ($requests as $key=>&$request) {
            if (is_scalar($request)) $request = (object)array('url' => $request);
              elseif (is_array($request)) $request = (object)$request;
            
            $ch = $this->curlInit(isset($request->method) ? $request->method : 'GET', $request->url, isset($request->params) ? $request->params : array(), isset($request->headers) ? $request->headers : array());
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }
        unset($request);
        
        // execute the handles
        $active = null; 
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh, $this->curl_opts[CURLOPT_TIMEOUT]) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }
        
        // get the results and clean up
        foreach ($handles as $key=>$ch) {
            $result = curl_multi_getcontent($ch);
            $error = curl_error($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contenttype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            
            $request = $requests[$key];
            
            curl_close($ch);
            curl_multi_remove_handle($mh, $ch);

            if ($error || $httpcode >= 300) {
                $this->multiRequestErrors[$key] = "HTTP {$request->method} request for '{$request->url}' failed: " . ($error ?: $this->httpError($httpcode, $contenttype, $result));
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

        if ($headers) {
            foreach ($headers as $key=>$value) {
                if (is_int($key)) continue;
                unset($headers[$key]);
                $headers[] = "$key: $value";
            }
            
            if (isset($this->curl_opts[CURLOPT_HTTPHEADER])) $headers = array_merge($this->curl_opts[CURLOPT_HTTPHEADER], $headers);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        if ($method == 'POST') {
            if (!isset($headers['Content-Type']) || $headers['Content-Type'] != 'multipart/form-data') $params = self::buildHttpQuery($params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        return $ch;
    }
    
    /**
     * Get error from HTTP result.
     * 
     * @param int    $httpcode
     * @param string $contenttype
     * @param string $result
     * @return string
     */
    static private function httpError($httpcode, $contenttype, $result)
    {
        if (is_string($result)) $data = json_decode($result);
        
        // Not JSON
        if (!isset($data)) return (strpos($contenttype, 'text/html') === false ? $result . ' ' : '') . "($httpcode)";
        
        // JSON
        if (is_scalar($data)) return $data;
          elseif (isset($data->error)) return is_scalar($data->error) ? $data->error : $data->error->message;
          elseif (isset($data->errors)) return is_scalar($data->errors[0]) ? $data->errors[0] : $data->errors[0]->message;
          elseif (isset($data->error_msg)) return $data->error_msg;
        
        return $result; // Return the JSON as string (this shouldn't happen)
    }

    /**
     * Redirect to URL and exit.
     * 
     * @param string $url
     */
    static protected function redirect($url)
    {
        echo 'Redirecting you to <a href="' . htmlentities($url) . '">' . $url . '</a>';
        header("Location: " . $url);
        exit();
    }


    /**
     * Get the URL of the current script.
     *
     * @param string $page    Relative path to page
     * @param array  $params  Parameters to overwrite
     * @return string
     */
    static public function getCurrentUrl($page=null, array $params=array())
    {
        if (strpos($page, '://') !== false) return self::buildUrl($page, $params);
        
        if (!isset($_SERVER['HTTP_HOST'])) return null;

        if (!isset($page)) $page = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        if ($page[0] != '/') {
            $dir = dirname($_SERVER['REQUEST_URI']);
            $page = ($dir == '.' ? '' : $dir) . '/' . $page;
        }
        
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
        
        $parts = parse_url($url) + array('path' => '/');

        if (isset($parts['query'])) {
            $query_params = array();
            parse_str($parts['query'], $query_params);
            $params = $overwrite ? array_merge($query_params, $params) : $query_params + $params;
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
     * Run a single prepared HTTP request.
     * 
     * @param object  $request  { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': mixed }
     * @return string
     */
    abstract public function doRequest($request);
    
    /**
     * Run multiple HTTP requests in parallel.
     * 
     * @param array   $requests  Array of value objects { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': mixed }
     * @param mixed   $convert   Convert to entity/collection (boolean) or callback for all requests
     * @return array
     */
    abstract public function doMultiRequest(array $requests);
    

    /**
     * Fetch from web service.
     * 
     * @param string  $resource
     * @param array   $params
     * @param mixed   $convert   Convert to entity/collection (boolean), object to be updated or callback
     * @return Entity|Collection|mixed
     */
    public function get($resource, array $params=array(), $convert=true)
    {
        $request = (object)array('method' => 'GET', 'url' => $resource, 'params' => $params, 'convert' => $convert);

        if ($this->prepared) return $this->addPreparedRequest($request);
        
        return $this->doRequest($request);
    }
    
    /**
     * Post to web service.
     * 
     * @param string  $resource
     * @param array   $params    POST parameters
     * @param mixed   $convert   Convert to entity/collection (boolean), object to be updated or callback
     * @return Entity|Collection|mixed
     */
    public function post($resource, array $params=array(), $convert=true)
    {
        $request = (object)array('method' => 'POST', 'url' => $resource, 'params' => $params, 'convert' => $convert);

        if ($this->prepared) return $this->addPreparedRequest($request);
        
        return $this->doRequest($request);
    }
    
    
    /**
     * Convert data to Entity, Collection or DateTime.
     * 
     * @param mixed   $data
     * @param string  $type     Entity type
     * @param boolean $stub     If an Entity, asume it's a stub
     * @param object  $request  Request used to get this data
     * @return Entity|Collection|DateTime|mixed
     */
    abstract public function convertData($data, $type=null, $stub=Entity::NO_STUB, $request=null);
}
