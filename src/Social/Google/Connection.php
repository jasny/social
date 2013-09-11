<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Google;

use Social\Connection as Base;

/**
 * Connection for the Google API's.
 * @see http://developers.Google.com/docs/api/reference
 * @package Google
 * 
 * Before you start register your application at https://code.google.com/apis/console/#access and retrieve a client ID
 *  and secret. You might also need to enable services at https://code.google.com/apis/console/ and retrieve an API key.
 */
class Connection extends Base implements \Social\Auth
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
     * If the clientSecret is omited, it's assumed the first argument is the API key.
     * Passing a user id is not required to act as the user, you're only required to specify the access token.
     * 
     * @param string        $clientId       Application's client ID (for OAuth2)
     * @param string        $clientSecret   Application's client secret (for OAuth2)
     * @param array|object  $access         [ user's access token, expire timestamp, google user id ] or { 'token': string, 'expires': unixtime, 'user': google user id }
     * @param string        $apiKey         Applications's API key
     */
    public function __construct($clientId, $clientSecret=null, $access=null, $apiKey=null)
    {
        if (!isset($clientSecret) && !isset($access) && !isset($apiKey)) {
            $apiKey = $clientId;
            $clientId = null;
        }
        
        $this->setCredentials($clientId, $clientSecret);
        $this->setAccessInfo($access);
        $this->apiKey = $apiKey;
        
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
    protected static function getUrl($url, array $params=[])
    {
        if ($url == 'oauth2/token') return dirname(self::authURL) . '/token';
        
        if (strpos($url, '://') === false) {
            $path = isset($this->apiName) ? "{$this->apiName}/{$this->apiVersion}/" : '';
            $url = static::apiUrl . $path . ltrim($url, '/');
        }
        
        return static::buildUrl($url, $params);
    }
    
    /**
     * Initialise an HTTP request object.
     *
     * @param object|string  $request  url or { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': mixed }
     * @return object
     */
    protected function initRequest($request)
    {
        $request = parent::initRequest($request);

        $glue = strpos($request->url, '?') === false ? '?' : '&';
        
        if ($this->accessToken) $request->url .= $glue . "oauth_token={$this->accessToken}";
         elseif (!isset($this->clientSecret)) $request->url .= $glue . "key={$this->clientId}";

        return $request;
    }
    
    /**
     * Set the authorization scope.
     * 
     * @param array|string $scope
     */
    protected function setScope($scope)
    {
        if (isset($scope)) {
            $scope = (array)$scope;
            foreach ($scope as &$item) {
                if (strpos($item, '://') === false) $item = static::apiURL . "auth/$item";
            }
        }
        
        $this->scope = $scope;
    }
    
    
    /**
     * Get current user profile.
     * 
     * @return object
     */
    public function me()
    {
        // Use absolute URL, so this will also work when a different API is selected.
        return $this->get(static::apiUrl . 'oauth2/v2/userinfo');
    }
    
    
    /**
     * Get a connection to one of the Google APIs.
     * @see https://developers.google.com/apis-explorer/
     * 
     * Specifying the version is recommended. If you omit the version, the preferred version will be fetched using the
     * discovery service.
     * 
     * @param string $name     API name
     * @param string $version  API version
     * @return Connection
     */
    public function api($name, $version=null)
    {
        if (!isset($version)) {
            $apis = $this->get(self::discoveryURL . "apis", 
                ['name'=>$name, 'preferred'=>true, "fields"=>'items/version'], false);
            
            if (empty($apis->items)) throw new \Exception("This Google $name API is not available");
            $version = $apis->items[0]->version;
        }
        
        $connection = new static($this->clientId, $this->clientSecret, $this->getAccessInfo(), $this->apiKey);
        $connection->apiName = $name;
        $connection->apiVersion = $version;
        
        return $connection;
    }
    
    /**
     * Discover this API.
     * @see https://developers.google.com/discovery/
     * 
     * @return object
     */
    public function discover()
    {
        return $this->get(self::discoveryURL . "apis/{$this->apiName}/{$this->apiVersion}/rest", false);
    }
    
    
    /**
     * Get the Google Maps API
     * 
     * @return \Social\GoogleMaps\Connection
     */
    public function maps()
    {
        return new \Social\GoogleMaps\Connection($this->apiKey);
    }
    
    /**
     * Get the Google+ API
     * 
     * @return \Social\GooglePlus\Connection
     */
    public function plus()
    {
        return new \Social\GooglePlus\Connection($this->clientId, $this->clientSecret, $this->getAccessInfo(),
            $this->apiKey);
    }
    
    /**
     * Get the YouTube API
     * 
     * @return \Social\YouTube\Connection
     */
    public function youtube()
    {
        return new \Social\YouTube\Connection($this->clientId, $this->clientSecret, $this->getAccessInfo(),
            $this->apiKey);
    }
}
