<?php
/**
 * Jasny Social
 * World's best PHP library for Social APIs
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
    
    /**
     * Name of the API service
     */
    const apiName = 'soundcloud';
    
    
    /**
     * SoundCloud API URL
     */
    const apiURL = "https://api.soundcloud.com/";
    
    /**
     * SoundCloud authentication URL
     */
    const authURL = "https://soundcloud.com/connect";


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
     * @param string        $appId          Application's client ID
     * @param string        $secret         Application's client secret
     * @param array|object  $access         [ user's, access token, expire timestamp, SoundCloud id ] or { 'token': string, 'expires': unixtime, 'user': SoundCloud id }
     */
    public function __construct($clientId, $clientSecret, $access=null)
    {
        $this->setCredentials($clientId, $clientSecret);
        $this->setAccessInfo($access);
        
        $this->curl_opts[CURLOPT_HTTPHEADER] = array('Accept: application/json');
    }
    
    
    /**
     * Fetch the OAuth2 access token.
     * 
     * @param array  $params  Parameters
     * @return object
     */
    protected function fetchAccessToken(array $params)
    {
        return $this->post('oauth2/token', $params);
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

        if (isset($request->params['oauth_token']) || isset($request->params['client_id'])) {
            // do nothing
        } elseif ($this->accessToken && !in_array($request->url, $this->publicResources)) {
            $request->params['oauth_token'] = $this->accessToken;
        } else {
            $request->params['client_id'] = $this->clientId;
        }

        return $request;
    }

    /**
     * Do a get request using the SoundCloud.com URL
     */
    public function resolve($url, array $params=[])
    {
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
