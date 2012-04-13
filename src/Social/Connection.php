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
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'jasny-social-1.0',
        CURLOPT_HTTPHEADER     => array('Expect:'),
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
        
        foreach ($params as $key=>&$value) {
            if (!isset($value)) unset($params[$key]);
            if (is_array($value)) $value = join(',', $value);
        }
        if (!empty($params)) $url .= '?' . http_build_query($params, null, '&');

        return $url;
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
        if ($result === false) $exception = new Exception("Failed do HTTP request for '" . preg_replace('/\?.*/', '', $url) . "': " . curl_error($ch));

        curl_close($ch);

        if (isset($exception)) throw $exception;
        return $result;
    }
    
    
    /**
     * Get the URL of the current script.
     *
     * @package array $params
     * @return string
     */
    public static function getRequestUrl(array $params=array())
    {
        if (!isset($_SERVER['HTTP_HOST'])) return null;

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
        $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parts = parse_url($currentUrl);

        // Set/remove parameters
        if (empty($params)) {
            $query = $parts['query'];
        } else {
            if (isset($parts['query'])) {
                $query_params = array();
                parse_str($parts['query'], $query_params);
                $params += $query_params;
            }

            foreach ($params as $key=>$value) {
                if (!isset($value)) unset($params[$key]);
            }
            $query = !empty($params) ? '?' . http_build_query($params, null, '&') : '';
        }

        // Use port if non default
        $port = isset($parts['port']) && (($protocol === 'http://' && $parts['port'] !== 80) || ($protocol === 'https://' && $parts['port'] !== 443)) ? ':' . $parts['port'] : '';

        // Rebuild
        return $protocol . $parts['host'] . $port . $parts['path'] . $query;
    }
}   
