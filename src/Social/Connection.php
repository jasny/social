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
    private static $CURL_OPTS = array(
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
     * Do an HTTP GET request and fetch data
     * 
     * @param string $url     Absolute or relative URL
     * @param array  $params  GET parameters
     * @return string
     */
    protected function request($url, array $params=array())
    {
        $url = $this->getUrl($url, $params);
        return $this->makeRequest($url);
    }

    /**
     * Do an HTTP POST request and fetch data
     * 
     * @param string $url     Absolute or relative URL
     * @param array  $params  POST parameters
     * @return string
     */
    protected function post($url, array $params=array())
    {
        $url = $this->getUrl($url);
        return $this->makeRequest($url, (array)$params);
    }
    
    /**
     * Do an HTTP request
     * 
     * @param string $url
     * @param array  $post  POST parameters
     */
    protected function makeRequest($url, $params=null)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, self::$CURL_OPTS);
        if (isset($params)) curl_setopt(CURLOPT_POSTFIELDS, $params);

        $result = curl_exec($ch);
        if ($result === false) throw new Exception("Failed do HTTP request for '" . preg_replace('/\?.*/', '', $url) . "': " . curl_error($ch));

        if (isset($exception)) throw $exception;
        return $result;
    }
    
    
    /**
     * Get the URL of the current script.
     *
     * @package array $params
     * @return string
     */
    static public function getRequestUrl(array $params=array())
    {
        if (!isset($_SERVER['HTTP_HOST'])) return null;

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
        $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
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
