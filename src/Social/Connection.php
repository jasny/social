<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
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
    public $curl_opts = array(
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
     * @param Entity|Result $target  Sets data of target by calling $target->processResult()
     * @param Connection $this
     */
    public function prepare($target=null)
    {
        $prepared = (object)['target'=>$target, 'requests'=>[], 'parent'=>$this->prepared];

        if ($this->prepared) $this->prepared->requests[] = $prepared;
        $this->prepared = $prepared;
        
        return $this;
    }

    /**
     * Execute buffered requests.
     * 
     * @return array|null
     */
    public function execute()
    {
        if ($this->prepared->parent) {
            $target = $this->prepared->target;
            $this->prepared = $this->prepared->parent;
            return $target;
        }
        
        $prepared = $this->resetPrepared();
        $requests = $this->getPreparedRequests($prepared);
        $results = $this->multiRequest($requests);
        
        return $this->handlePrepared($prepared, $requests, $results);
    }

    /**
     * Execute buffered requests for different Social connenctions.
     * 
     * @param Connection $connection  Social connection with prepared requests
     * @param ...
     * @return \SplObjectStorage
     */
    static public function execAll(Connection $connection)
    {
        $connections = func_get_args();
        $prepared = [];
        $requests = [];
        $handles = [];
        $ret = new \SplObjectStorage();
        
        $mh = curl_multi_init();
        
        foreach ($connections as $i=>$connection) {
            if (!$connection instanceof self) trigger_error("Incorrect use of " . __NAMESPACE__ . "\\" . __CLASS__
                . ": Argument $i is not a Connection object", E_USER_ERROR);
            
            if (!isset($connection->prepared)) continue;

            $prepared[$i] = $connection->resetPrepared();
            $requests[$i] = $connection->getPreparedRequests($prepared[$i]);
            $handles[$i] = $connection->mulitRequestInit($mh, $requests[$i]);
        }
        
        do {
            curl_multi_exec($mh, $running);
        } while ($running);
        
        foreach ($connections as $i=>$connection) {
            $results = $connection->multiRequestHandle($mh, $requests[$i], $handles[$i]);
            $ret[$connection] = $connection->handlePrepared($prepared[$i], $requests[$i], $results);
        }
        
        curl_multi_close($mh);
        return $ret;
    }
    

    /**
     * Add a request to the prepare stack.
     * 
     * @param object $request
     * @return Connection $this
     */
    protected function addPreparedRequest($request)
    {
        if (is_array($request)) {
            $prepared = (object)['target'=>null, 'requests'=>$request, 'parent'=>$this->prepared];
            $this->prepared->requests[] = $prepared;
        } else {
            $this->prepared->requests[] = $request;
        }
        
        return $this;
    }

    /**
     * Get all requests from prepare stack.
     * 
     * @param array $prepared  Prepare stack
     * @return array
     */
    private function getPreparedRequests($prepared)
    {
        $requests = [];
        
        foreach ($prepared->requests as $request) {
            if (isset($request->requests)) $requests += $this->getPreparedRequests($request);
              else $requests[] = $request;
        }
        
        return $requests;
    }
    
    /**
     * Reset the prepare stack.
     * 
     * @return array  old prepare stack
     */
    private function resetPrepared()
    {
        if ($this->prepared->parent) throw new \Exception("Connection has multiple stacked preparations");
        
        $prepared = $this->prepared;
        $this->prepared = null;
        
        return $prepared;
    }

    /**
     * Process results from prepared requests.
     * 
     * @param array $prepared
     * @param array $requests
     * @param array $results
     * @return array|Collection|Result
     */
    private function handlePrepared($prepared, $requests, $results)
    {
        $ret = [];
        
        foreach ($prepared->requests as $i=>$request) {
            if (isset($request->requests)) {
                $result = $this->handlePrepared($request, $requests, $results);
            } else {
                $key = array_search($request, $requests, true);
                $result = isset($results[$key]) ? $results[$key] : null;
            }

            if ($prepared->target) $prepared->target->processResult($result, $i);
             else $ret[$i] = $result;
        }
        
        return $prepared->target ?: $ret;
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
        $info = (object)curl_getinfo($ch);
        
        curl_close($ch);
        
        $result = $request->method === 'HEAD' ? $info : $this->decodeResponse($info, $response);

        if ($error || $info->http_code >= 300) {
            if (!$error) $error = static::httpError($info, $result, $request);
            if ($error !== false) throw new \Exception("HTTP " . (@$request->method ?: 'GET') . " request for '" .
                $this->getFullUrl($request->url). "' failed: $error");
        }
        
        return $result;
    }

    
    /**
     * Initialise a curl multi request handler
     * 
     * @param resource $mh
     * @param array    $requests
     * @return array
     */
    private function mulitRequestInit($mh, array &$requests)
    {
        // prepare requests and handles
        $handles = [];
        
        foreach ($requests as $key=>&$request) {
            if (!isset($request)) continue;
            $request = $this->initRequest($request);
            
            $ch = $this->curlInit($request);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }
        
        return $handles;
    }
    
    /**
     * Handle curl multi request responses
     * 
     * @param resource $mh
     * @param array $requests
     * @param array $handles
     */
    private function multiRequestHandle($mh, array $requests, array $handles)
    {
        $results = [];
        
        // get the results and clean up
        foreach ($handles as $key=>$ch) {
            $request = $requests[$key];
            
            $response = curl_multi_getcontent($ch);            
            $error = curl_error($ch);
            $info = (object)curl_getinfo($ch);
            
            curl_close($ch);
            curl_multi_remove_handle($mh, $ch);

            $result = $request->method === 'HEAD' ? $info : $this->decodeResponse($info, $response);

            if ($error || $info->http_code >= 300) {
                if (!$error) $error = static::httpError($info, $result, $request);
                if ($error !== false) {
                    trigger_error("HTTP " . (@$request->method ?: 'GET') . " request for '" .
                        $this->getFullUrl($request->url) . "' failed: {$error}", E_USER_WARNING);
                    continue;
                }
            }
            
            $results[$key] = $result;
        }
        
        return $results;
    }
    
    /**
     * Run multiple HTTP requests in parallel.
     * 
     * @param array $requests  Array of value objects
     * @return array
     */
    protected function multiRequest(array $requests)
    {
        $mh = curl_multi_init();
        
        $handles = $this->mulitRequestInit($mh, $requests);
        
        do {
            curl_multi_exec($mh, $running);
        } while ($running);
        
        $results = $this->multiRequestHandle($mh, $requests, $handles);
        
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
        if ($request->method == 'HEAD') curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // set headers
        $headers = array();
        $rawheaders = $request->headers;
        if (isset($this->curl_opts[CURLOPT_HTTPHEADER])) 
            $rawheaders = array_merge($this->curl_opts[CURLOPT_HTTPHEADER], $rawheaders);
       
        foreach ($rawheaders as $key=>$value) {
            $headers[] = is_int($key) ? $value : "$key: $value";
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // set post fields
        if ($request->method == 'POST') {
            if (isset($rawheaders['Content-Type']) && $rawheaders['Content-Type'] == 'multipart/form-data') {
                $params = $request->params;
            } else if (isset($rawheaders['Content-Type']) && $rawheaders['Content-Type'] == 'application/json') {
                $params = json_encode($request->params);
            } else {
                $params = self::buildHttpQuery($request->params);
            }
            
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
     * Process the body.
     * 
     * @param object $info         Curl info
     * @param string $response     The HTTP response
     * @return mixed
     */
    protected function decodeResponse($info, $response)
    {
        list($contenttype) = explode(';', $info->content_type);
        
        if ($contenttype == 'application/json') return json_decode($response);
        if ($contenttype == 'text/xml') return simplexml_load_string($response);
        return $response;
    }
    
    /**
     * Get error from HTTP result.
     * 
     * @param object $info
     * @param mixed  $result
     * @param object $request
     * @return string
     */
    protected static function httpError($info, $result=null, $request=null)
    {
        switch ($info->http_code) {
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
            default:  return $info->http_code;
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
        if (strpos($page, '://') !== false) return self::getFullUrl($page, $params);
        
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
    public function get($resource, $params=[])
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
    public function post($resource, $params=[])
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
    public function put($resource, $params=[])
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
    public function delete($resource, $params=[])
    {
        return $this->request((object)['method'=>'DELETE', 'url'=>$resource, 'params'=>$params]);
    }
    
    /**
     * Do nothing, but do increment the prepared request counter.
     */
    public function nop()
    {
        if ($this->prepared) return $this->addPreparedRequest(null);
    }
}
