<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\SoundCloud;

use Social\Connection as Base;

/**
 * SoundCloud  API connection.
 * @see http://developers.soundcloud.com/docs/api/reference
 * @package SoundCloud
 * 
 * Before you start register your application at http://soundcloud.com/you/apps/ and retrieve a client ID and secret
 */
class Connection extends Base implements \Social\Auth
{
    use \Social\OAuth2;
    use \Social\OEmbed;
    
    /**
     * Name of the API's service provider
     */
    const serviceProvider = 'soundcloud';
    
    
    /**
     * SoundCloud API URL
     */
    const apiURL = "https://api.soundcloud.com/";
    
    /**
     * SoundCloud authentication URL
     */
    const authURL = "https://soundcloud.com/connect";

    /**
     * SoundCloud website URL
     */
    const websiteURL = "http://www.soundcloud.com/";

    /**
     * A list of resources for which only client_id should be passed and never access_token
     */
    protected $publicResources = [
       'resolve'
    ];
    
    /**
     * Class constructor.
     * 
     * Passing a user id is not required to act as the user, you're only required to specify the access token.
     * 
     * @param string $clientId      Application's client ID
     * @param string $clientSecret  Application's client secret
     * @param array  $access        [ user's access token, expire timestamp, SoundCloud id ] or { 'token': string, 'expires': unixtime, 'user': SoundCloud id }
     */
    public function __construct($clientId, $clientSecret, $access=null)
    {
        $this->setCredentials($clientId, $clientSecret);
        $this->setAccessInfo($access);
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

        if ($this->accessToken && !in_array($request->url, $this->publicResources)) {
            $request->queryParams['oauth_token'] = $this->accessToken;
        } else {
            $request->queryParams['client_id'] = $this->clientId;
        }

        return $request;
    }

    /**
     * Get error from HTTP result.
     * 
     * @param int   $httpcode
     * @param mixed $result  
     * @return string
     */
    static protected function httpError($httpcode, $result)
    {
        if (is_object($result) && $result->errors) return $result->errors[0]->error_message;
        return parent::httpError($httpcode, $result);
    }


    /**
     * Do a get request using the SoundCloud.com URL
     * 
     * @param string $url
     * @param array  $params
     * @return object|mixed
     */
    public function resolve($url, array $params=[])
    {
        if (strpos($url, '://') === false) $url = self::websiteURL . ltrim($url, '/');
        return $this->get('resolve', compact('url') + $params);
    }

    /**
     * Get current user profile.
     * 
     * @return object
     */
    public function me()
    {
        return $this->get('me');
    }
}
