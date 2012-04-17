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
        CURLOPT_HTTPHEADER          => array('Expect:'),
        CURLOPT_FAILONERROR         => true,
        CURLOPT_FOLLOWLOCATION      => true,
        CURLOPT_MAXREDIRS           => 3,
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
     * @param string $type     GET, POST or DELETE
     * @param string $url
     * @param array  $params   REQUEST parameters
     * @param array  $headers  Additional HTTP headers
     */
    protected function httpRequest($type, $url, $params=null, array $headers=array())
    {
        $url = $this->getUrl($url, $type != 'POST' ? $params : array());        

        $ch = curl_init($url);
        curl_setopt_array($ch, static::$CURL_OPTS);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        if ($type == 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        if ($headers) {
            foreach ($headers as $key=>&$value) {
                if (!is_int($key)) $value = "$key: $value";
            }
            unset($value);
            if (isset(static::$CURL_OPTS[CURLOPT_HTTPHEADER])) $headers = array_merge(static::$CURL_OPTS[CURLOPT_HTTPHEADER], $headers);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $result = curl_exec($ch);
        if ($result === false) throw new Exception("Failed do HTTP request for '" . preg_replace('/\?.*/', '', $url) . "': " . curl_error($ch));

        if (isset($exception)) throw $exception;
        return $result;
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
}   
