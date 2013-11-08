<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
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
    const defaultExtension = '';

    /**
     * Default options for curl.
     * 
     * @var array
     */
    protected $curl_opts = array(
        CURLOPT_CONNECTTIMEOUT      => 10,
        CURLOPT_TIMEOUT             => 60,
        CURLOPT_USERAGENT           => 'JasnySocial/0.2',
        CURLOPT_HTTPHEADER          => ['Accept'=>'application/json, */*'],
        CURLOPT_ENCODING            => '',
        CURLOPT_FOLLOWLOCATION      => true, 
        CURLOPT_MAXREDIRS           => 3,
        CURLOPT_TIMEOUT             => 10,
    );

    /**
     * HTTP request query paramaters to be added for each request
     * @var $array
     */
    protected $queryParams = [];
    
    /**
     * Prepared requests stack
     * @var $array
     */
    protected $prepared;

    
    /**
     * Set an HTTP query parameter to be used in each request
     * 
     * @param string|array $param  Paramater key or associated array
     * @param mixed        $value
     * @return Connection $this
     */
    public function setQueryParam($param, $value=null)
    {
        if (is_array($param)) $this->queryParams = $param = $this->queryParams;
         else $this->queryParams[$param] = $value;
         
        return $this;
    }
    
    /**
     * Get an HTTP query parameter used in each request
     * 
     * @param string $param  Paramater key
     * @return 
     */
    public function getQueryParam($param)
    {
        return isset($this->queryParams[$param]) ? $this->queryParams[$param] : null;
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
     * Build an absolute url, relative to the api url.
     * 
     * @param string $url
     * @param array  $params
     */
    protected function getFullUrl($url, array $params=[])
    {
        if (strpos($url, '://') === false) $url = static::apiURL . ltrim($url, '/');
        return self::buildUrl($url, $params);
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
     * @return Connection $this
     */
    protected function addPreparedRequest($request)
    {
        if (is_array($request) && is_int(key($request))) {
            $this->prepared->requests = array_merge($this->prepared->requests, $request);
        } else {
            $this->prepared->requests[] = $request;
        }
        
        return $this;
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
     * @param object|string $request  value object or url
     * @return object
     */
    protected function initRequest($request)
    {
        if (is_scalar($request)) $request = (object)['url'=>$request];
          elseif (is_array($request)) $request = (object)$request;
        
        if (!isset($request->url)) {
            if (isset($request->resource)) $request->url = $request->resource;
              else throw new \Exception("Invalid request, no URL specified");
        }

        if (!isset($request->method)) $request->method = 'GET';
        if (!isset($request->headers)) $request->headers = [];
        if (!isset($request->queryParams)) $request->queryParams = [];

        $request->params = (isset($request->params) ? $request->params : []) + static::getDefaultParams($request->url);
        $request->url = $this->processPlaceholders($request->url, $request->params);

        list($url, $query) = explode('?', $request->url, 2) + [1=>null];
        if ($query) {
            parse_str($query, $params);
            $request->queryParams += $params;
            $query = null;
        }
        $request->queryParams += $this->queryParams;

        if (static::defaultExtension && pathinfo($url, PATHINFO_EXTENSION) == '' && empty($request->no_ext)) {
            $request->url = $url . '.' . static::defaultExtension;
        }
        
        if ($this->detectMultipart($request)) $request->headers['Content-Type'] = 'multipart/form-data';
        
        return $request;
    }
    
    
    /**
     * Run prepared HTTP request(s).
     * 
     * @param object|array $request  Values object or array of value objects
     * @return mixed
     */
    protected function request($request)
    {
        if ($this->prepared) return $this->addPreparedRequest($request);
        
        return !is_array($request) ? $this->singleRequest($request) : $this->multiRequest($request);
    }
    
    /**
     * Run a single HTTP request.
     * 
     * @param object $request  Value object
     * @return string
     */
    protected function singleRequest($request)
    {
        $request = $this->initRequest($request);
        
        $ch = $this->curlInit($request);
        
        $response = curl_exec($ch);        
        $error = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        list($contenttype) = explode(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        
        curl_close($ch);

        $result = $this->decodeResponse($contenttype, $response);

        if ($error || $httpcode >= 300) {
            if (!$error) $error = static::httpError($httpcode, $result);
            if ($error !== false) throw new \Exception("HTTP " . (@$request->method ?: 'GET') . " request for '" .
                $this->getFullUrl($request->url). "' failed: $error");
        }
        
        return $result;
    }

    /**
     * Run multiple HTTP requests in parallel.
     * 
     * @param array $requests  Array of value objects
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
            $request = $requests[$key];
            
            $response = curl_multi_getcontent($ch);            
            $error = curl_error($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            list($contenttype) = explode(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
            
            curl_close($ch);
            curl_multi_remove_handle($mh, $ch);

            $result = $this->decodeResponse($contenttype, $response);

            if ($error || $httpcode >= 300) {
                if (!$error) $error = static::httpError($httpcode, $result);
                if ($error !== false) {
                    trigger_error("HTTP " . (@$request->method ?: 'GET') . " request for '" .
                        $this->getFullUrl($request->url) . "' failed: {$error}", E_USER_WARNING);
                    continue;
                }
            }
            
            $results[$key] = $result;
        }
        
        curl_multi_close($mh);
        return $results;
    }
    
    /**
     * Initialize a cURL session.
     * 
     * @param object $request
     * @return resource
     */
    protected function curlInit($request)
    {
        $query_params = $request->method != 'POST' ? $request->params : [];
        $query_params += $request->queryParams + $this->queryParams;
        $url = $this->getFullUrl($request->url, $query_params);

        // init
        $ch = curl_init($url);
        curl_setopt_array($ch, $this->curl_opts);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // set headers
        $rawheaders = $request->headers;
        if (isset($this->curl_opts[CURLOPT_HTTPHEADER])) 
            $rawheaders = array_merge($this->curl_opts[CURLOPT_HTTPHEADER], $rawheaders);
        
        foreach ($rawheaders as $key=>$value) {
            $headers[] = is_int($key) ? $value : "$key: $value";
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
     * Remove the headers and process the body.
     * 
     * @param string $contenttype  Mime type
     * @param string $response     The HTTP response
     * @return mixed
     */
    protected function decodeResponse($contenttype, $response)
    {
        if ($contenttype == 'application/json') return json_decode($response);
        if ($contenttype == 'text/xml') return simplexml_load_string($response);
        return $response;
    }
    
    /**
     * Get error from HTTP result.
     * 
     * @param int   $httpcode
     * @param mixed $result
     * @return string
     */
    protected static function httpError($httpcode, $result)
    {
        switch ($httpcode) {
            case 400: return '400 Bad Request';
            case 401: return '401 Unauthorized';
            case 402: return '402 Payment Required';
            case 403: return '403 Forbidden';
            case 404: return '404 Not Found';
            case 405: return '405 Method Not Allowed';
            case 410: return '410 Gone';
            case 500: return '500 Internal Server Error';
            case 501: return '501 Not Implemented';
            case 503: return '503 Not Implemented';
            case 509: return '509 Bandwidth Limit Exceeded';
            default:  return $httpcode;
        }
    }

    /**
     * Redirect to URL and exit.
     * 
     * @param string $url
     */
    protected static function redirect($url)
    {
        echo 'Redirecting you to <a href="' . htmlentities($url) . '">' . $url . '</a>';
        header("Location: $url");
        exit();
    }


    /**
     * Get the URL of the current script.
     *
     * @param string $page    Relative path to page
     * @param array  $params  Parameters to overwrite
     * @return string
     */
    protected static function getCurrentUrl($page=null, array $params=[])
    {
        if (strpos($page, '://') !== false) return $this->getFullUrl($page, $params);
        
        if (!isset($_SERVER['HTTP_HOST'])) return null;

        if (!isset($page)) $page = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        if ($page[0] != '/') {
            $dir = dirname($_SERVER['REQUEST_URI']);
            $page = ($dir == '.' ? '' : $dir) . '/' . $page;
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
        $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $page;
        
        return static::buildUrl($currentUrl, $params);
    }
    
    /**
     * Build a url adding query paramaters.
     * 
     * @param string $url
     * @param array  $params
     */
    protected static function buildUrl($url, array $params)
    {
        if (empty($params)) return $url;
        
        $parts = parse_url($url) + array('path' => '/');

        if (isset($parts['query'])) {
            $query_params = [];
            parse_str($parts['query'], $query_params);
            $params += $query_params;
        }

        $query = static::buildHttpQuery($params);

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') .
            $parts['path'] . ($query ? '?' . $query : '');
    }
    
    /**
     * Build a HTTP query, converting arrays to a comma seperated list and removing null parameters.
     * 
     * @param type $params
     * @return string
     */
    protected static function buildHttpQuery($params)
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
     * Parse URL and return parameters.
     * 
     * @param string $url
     * @return array
     */
    protected static function extractParams($url)
    {
        $params = [];

        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) parse_str($query, $params);
        
        return $params;
    }

    
    /**
     * GET from the web service API.
     * 
     * @param string  $resource
     * @param array   $params    Query parameters
     * @return Entity|Collection|mixed
     */
    public function get($resource, array $params=[])
    {
        return $this->request((object)['method'=>'GET', 'url'=>$resource, 'params'=>$params]);
    }
            
    /**
     * POST to the web service API.
     * 
     * @param string  $resource
     * @param array   $params    POST parameters
     * @return Entity|Collection|mixed
     */
    public function post($resource, array $params=[])
    {
        return $this->request((object)['method'=>'POST', 'url'=>$resource, 'params'=>$params]);
    }
    
    /**
     * PUT to the web service API.
     * 
     * @param string  $resource
     * @param array   $params    Query parameters
     * @return Entity|Collection|mixed
     */
    public function put($resource, array $params=[])
    {
        return $this->request((object)['method'=>'PUT', 'url'=>$resource, 'params'=>$params]);
    }
    
    /**
     * DELETE from the web service API.
     * 
     * @param string  $resource
     * @param array   $params    Query parameters
     * @return Entity|Collection|mixed
     */
    public function delete($resource, array $params=[])
    {
        return $this->request((object)['method'=>'DELETE', 'url'=>$resource, 'params'=>$params]);
    }
}
