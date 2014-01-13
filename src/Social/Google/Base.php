<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Google;

/**
 * Base class for a Google API connection.
 * @link http://developers.Google.com/docs/api/reference
 * @package Google
 */
abstract class Base extends \Social\Connection implements \Social\Auth
{
    use \Social\OAuth2;
    
    /**
     * Name of the API's service provider
     */
    const serviceProvider = 'google';
    
    
    /**
     * Google API URL
     */
    const apiURL = "https://www.googleapis.com/";
    
    /**
     * Google discovery API URL
     */
    const discoveryURL = "https://www.googleapis.com/discovery/v1/";
    
    /**
     * Google authentication URL
     */
    const authURL = "https://accounts.google.com/o/oauth2/auth";
    
    
    /**
     * The application's API key
     * @var string
     */
    protected $apiKey;
    
    /**
     * Google API name
     * @var string 
     */
    protected $apiName;
    
    /**
     * Google API version
     * @var string 
     */
    protected $apiVersion;
    
    
    /**
     * Class constructor.
     * 
     * Passing a user id is not required to act as the user, you're only required to specify the access token.
     * 
     * @param string       $apiKey        Applications's API key
     * @param string       $clientId      Application's client ID (for OAuth2)
     * @param string       $clientSecret  Application's client secret (for OAuth2)
     * @param array|object $access        [ token, expires, me ] or { 'token': string, 'expires': unixtime, 'user': me }
     */
    public function __construct($apiKey=null, $clientId=null, $clientSecret=null, $access=null)
    {
        $this->apiKey = $apiKey;
        $this->setCredentials($clientId, $clientSecret);
        $this->setAccessInfo($access);
        
        // See https://developers.google.com/discovery/v1/performance#gzip
        $this->curl_opts[CURLOPT_USERAGENT] .= ' (gzip)';
    }
    
    /**
     * Get the application's API key
     * 
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
    
    
    /**
     * Get full URL.
     * 
     * @param string $url     Relative or absolute URL or a request object
     * @param array  $params  Parameters
     * @return string
     */
    protected function getFullUrl($url, array $params=[])
    {
        if (strpos($url, '://') === false) {
            $path = isset($this->apiName) ? "{$this->apiName}/{$this->apiVersion}/" : '';
            $url = static::apiURL . $path . ltrim($url, '/');
        }
        
        return static::buildUrl($url, $params);
    }
    
    /**
     * Initialise an HTTP request object.
     *
     * @param object|string  $request  url or value object
     * @return object
     */
    protected function initRequest($request)
    {
        $request = parent::initRequest($request);

        $request->queryParams['oauth_token'] = $this->accessToken;
        $request->queryParams['key'] = $this->apiKey;

        if (isset($request->params['fields'])) $request->queryParams['fields'] = $request->params['fields'];
        unset($request->params['fields']);
       
        
        return $request;
    }

    /**
     * Get error from HTTP result.
     * 
     * @param object $info
     * @param mixed  $result
     * @parma object $request
     * @return string
     */
    protected static function httpError($info, $result=null, $request=null)
    {
        if (is_object($result) && $result->error) return $result->error->code . ' - ' . $result->error->message;
        return parent::httpError($info, $result, $request);
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
            $value = rawurlencode($key) . '=' .
                (is_bool($value) ? ($value ? 'true' : 'false') : rawurlencode($value));
        }
       
        return join('&', $params);
    }  


    /**
     * Fetch the OAuth2 access token.
     * 
     * @param array  $params  Parameters
     * @return object
     */
    protected function fetchAccessToken(array $params)
    {
        return $this->post(dirname(static::authURL) . '/token', $params);
    }
    
    /**
     * Set the authorization scope.
     * 
     * @param array|string $scope
     * @return string
     */
    protected function setScope($scope)
    {
        if (isset($scope)) {
            $scope = (array)$scope;
            foreach ($scope as &$item) {
                if (strpos($item, '://') === false) $item = static::apiURL . "auth/$item";
            }
        } else {
            $scope = [static::apiURL . 'auth/userinfo.profile'];
        }
        
        $this->scope = $scope;
        return join(' ', $scope);
    }
    	

    /**
     * Get current user profile.
     * 
     * @return Me
     */
    public function me()
    {
        // Use absolute URL, so this will also work when a different API is selected.
        return new Me($this->get(static::apiURL . 'oauth2/v2/userinfo'));
    }
}
