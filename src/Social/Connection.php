<?php
/**
 * Jasny Social
 * World's best PHP library for Social APIs
 * 
 * @license http://www.jasny.net/mit MIT
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
     * Default file extension to be added for API calls.
     */
    protected $defaultExtension;
    
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
     * Get API base URL.
     * 
     * @param string $url  Relative URL
     * @return string
     */
    protected static function getBaseUrl($url=null)
    {
        return static::apiURL;
    }
    
    /**
     * Get default parameters for resource.
     * 
     * @param string $resource
     * @return array
     */
    protected static function getDefaultParams($resource)
    {
        return [];
    }

    /**
     * Check if resource requires a multipart POST.
     * 
     * @param string $resource
     * @return boolean 
     */
    protected static function detectMultipart($resource)
    {
        return false;
    }
    
    
    /**
     * Get full URL.
     * 
     * @param string $url     Relative or absolute URL or a request object
     * @param array  $params  Parameters
     */
    public function getUrl($url=null, array $params=[])
    {
        if (is_object($url)) {
            if (isset($url->params)) $params = $url->params + $params;
            $url = $url->url;
        }

        if (strpos($url, '://') === false) $url = static::getBaseUrl($url) . ltrim($url, '/');
        return static::buildUrl($url, $params);
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
        $prepared = (object)['target'=>$target, 'requests'=>[], 'parent'=>$this->prepared];

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
        $requests = [];
        
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
        $ret = [];
        
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
     * Initialise an HTTP request object.
     *
     * @param object|string  $request  url or { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': mixed }
     * @return object
     */
    protected function initRequest($request)
    {
        if (is_scalar($request)) $request = (object)array('url' => $request);
          elseif (is_array($request)) $request = (object)$request;
        
        if (!isset($request->url)) {
            if (isset($request->resource)) $request->url = $request->resource;
              else throw new \Exception("Invalid request, no URL specified");
        }

        if (!isset($request->method)) $request->method = 'GET';
        if (!isset($request->convert)) $request->convert = true;
        if (!isset($request->headers)) $request->headers = [];

        $request->params = (isset($request->params) ? $request->params : []) + static::getDefaultParams($request->url);
        $request->url = $this->processPlaceholders($request->url, $request->params);

        list($url, $params) = explode('?', $request->url, 2) + [1=>null];
        if ($params && $request->method == 'GET') {
            $request->params + $params;
            $params = null;
        }
        
        if ($this->defaultExtension && pathinfo($url, PATHINFO_EXTENSION) == '') {
            $request->url = "$url" . $this->defaultExtension . ($params ? "?$params" : '');
        }
        
        if ($this->detectMultipart($request)) $request->headers['Content-Type'] = 'multipart/form-data';
        
        return $request;
    }
    
    /**
     * Run prepared HTTP request(s).
     * 
     * @param object|array  $request  Value object or array of objects { 'method': string, 'url': string, 'params': array, 'headers': array, 'writefunction': callback  }
     * @return string
     */
    protected function request($request)
    {
        return !is_array($request) ? $this->singleRequest($request) : $this->multiRequest($request);
    }
    
    /**
     * Run a single HTTP request.
     * 
     * @param object $request  Value objects { 'method': string, 'url': string, 'params': array, 'headers': array, 'writefunction': callback }
     * @return string
     */
    protected function singleRequest($request)
    {
        $request = $this->initRequest($request);
        
        $ch = $this->curlInit($request);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        list($contenttype) = explode(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        curl_close($ch);

        if ($error || $httpcode >= 300) {
            if (!$error) $error = static::httpError($httpcode, $contenttype, $result);
            throw new \Exception("HTTP " . (@$request->method ?: 'GET') . " request for '" . $this->getUrl($request->url). "' failed: $error");
        }
        
        return $contenttype == 'application/json' ? json_decode($result) : $result;
    }

    /**
     * Run multiple HTTP requests in parallel.
     * 
     * @param array $requests  Array of value objects { 'method': string, 'url': string, 'params': array, 'headers': array, 'writefunction': callback }
     * @return array
     */
    protected function multiRequest(array $requests)
    {
        foreach ($requests as &$request) {
            $request = $this->initRequest($request);
        }
        
        $results = [];
        $this->multiRequestErrors = [];
        
        // prepare requests and handles
        $handles = [];
        $mh = curl_multi_init();
        
        foreach ($requests as $key=>&$request) {
            if (is_scalar($request)) $request = (object)['url' => $request];
              elseif (is_array($request)) $request = (object)$request;
            
            $ch = $this->curlInit($request);
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
            list($contenttype) = explode(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
            
            $request = $requests[$key];
            
            curl_close($ch);
            curl_multi_remove_handle($mh, $ch);

            if ($error || $httpcode >= 300) {
                if (!$error) $error = static::httpError($httpcode, $contenttype, $result);
                $msg = "HTTP " . (@$request->method ?: 'GET') . " request for '{$request->url}' failed: {$error}";
                trigger_error($msg, E_USER_WARNING);
            } else {
                $results[$key] = $contenttype == 'application/json' ? $result : json_decode($result);
            }
        }
        
        curl_multi_close($mh);
        return $results;
    }
    
    /**
     * Initialize a cURL session.
     * 
     * @param object  $request
     * @return resource
     */
    protected function curlInit($request)
    {
        $url = $this->getUrl($request->url, $request->method != 'POST' ? $request->params : []);

        // init
        $ch = curl_init($url);
        curl_setopt_array($ch, $this->curl_opts);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // set headers
        $headers = [];
        foreach ($request->headers as $key=>$value) {
            $headers[] = is_int($key) ? $value : "$key: $value";
        }

        if (isset($this->curl_opts[CURLOPT_HTTPHEADER])) {
            $headers = array_merge($this->curl_opts[CURLOPT_HTTPHEADER], $headers);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // set post fields
        if ($request->method == 'POST') {
            $params = isset($headers['Content-Type']) && $headers['Content-Type'] == 'multipart/form-data' ?
                $request->params :
                self::buildHttpQuery($request->params);
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        // set write function
        if (isset($request->writefunction)) {
            curl_setopt($ch, CURLOPT_TIMEOUT, 0); // Don't timeout
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, $request->writefunction);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
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
    static protected function httpError($httpcode, $contenttype, $result)
    {        
        if ($contenttype != 'application/json') {
            return ($contenttype === 'text/html' ? $result . ' ' : '') . "($httpcode)";
        }

        // JSON
        $data = json_decode($result, true);

        if (is_scalar($data)) return $data;
          elseif (isset($data['error'])) return is_scalar($data['error']) ? $data['error'] : reset($data['error']);
          elseif (isset($data['errors'])) return is_scalar($data['errors'][0]) ? $data['errors'][0] : reset($data['errors'][0]);
          elseif (isset($data['error_msg'])) return $data['error_msg'];
        
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
    static public function getCurrentUrl($page=null, array $params=[])
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
            $query_params = [];
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
        $params = [];

        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) parse_str($query, $params);
        
        return $params;
    }

    
    /**
     * De request for the web service API.
     * 
     * @param string  $method
     * @param string  $resource
     * @param array   $params
     * @param mixed   $convert   Convert to entity/collection (boolean), object to be updated or callback
     * @return Entity|Collection|mixed
     */
    protected function apiRequest($method, $resource, array $params=[], $convert=true)
    {
        $request = (object)['method'=>$method, 'url'=>$resource, 'params'=>$params, 'convert'=>$convert];
        
        if ($this->prepared) return $this->addPreparedRequest($request);
        return $this->request($request);
    }

    /**
     * GET from the web service API.
     * 
     * @param string  $resource
     * @param array   $params
     * @param mixed   $convert   Convert to entity/collection (boolean), object to be updated or callback
     * @return Entity|Collection|mixed
     */
    public function get($resource, array $params=[], $convert=true)
    {
        return $this->apiRequest('GET', $resource, $params, $convert);
    }
            
    /**
     * POST to the web service API.
     * 
     * @param string  $resource
     * @param array   $params    POST parameters
     * @param mixed   $convert   Convert to entity/collection (boolean), object to be updated or callback
     * @return Entity|Collection|mixed
     */
    public function post($resource, array $params=[], $convert=true)
    {
        return $this->apiRequest('POST', $resource, $params, $convert);
    }
    
    /**
     * PUT to the web service API.
     * 
     * @param string  $resource
     * @param array   $params    POST parameters
     * @param mixed   $convert   Convert to entity/collection (boolean), object to be updated or callback
     * @return Entity|Collection|mixed
     */
    public function put($resource, array $params=[], $convert=true)
    {
        return $this->apiRequest('PUT', $resource, $params, $convert);
    }
    
    /**
     * POST to the web service API.
     * 
     * @param string  $resource
     * @param array   $params    POST parameters
     * @param mixed   $convert   Convert to entity/collection (boolean), object to be updated or callback
     * @return Entity|Collection|mixed
     */
    public function delete($resource, array $params=[], $convert=true)
    {
        return $this->apiRequest('DELETE', $resource, $params, $convert);
    }
}
