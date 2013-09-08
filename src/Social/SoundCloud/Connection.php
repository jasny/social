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
use Social\Collection;

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
     * Paramater used as key for GET and SESSION
     */
    const AUTH_PARAM = 'soundcloud_auth';
    
    
    /**
     * SoundCloud API URL
     */
    const apiURL = "https://api.soundcloud.com/";
    
    /**
     * SoundCloud authentication URL
     */
    const authURL = "https://soundcloud.com/connect";

    
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
     * Get full URL.
     * 
     * @param string $url     Relative or absolute URL or a request object
     * @param array  $params  Parameters
     */
    public function getUrl($url=null, array $params=[])
    {
        if ($url == 'oauth/access_token') $url = 'oauth2/token';
        parent::getUrl($url, $params);
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
