<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Facebook;

use Social\Connection as Base;

/**
 * Facebook Graph API connection.
 * @link http://developers.facebook.com/docs/reference/api/
 * @package Facebook
 * 
 * Before you start register your application at https://developers.facebook.com/apps and retrieve a client ID and
 *  secret.
 */
class Connection extends Base implements \Social\Auth
{
    use \Social\OAuth2;
    
    /**
     * Facebook API version.
     * @var string
     */
    public $apiVersion = 'v2.2';
    
    /**
     * Name of the API service
     */
    const serviceProvider = 'facebook';
    
    /**
     * Facebook Open Graph API URL
     */
    const apiURL = "https://graph.facebook.com/{v}/";
    
    /**
     * Facebook authentication URL
     */
    const authURL = "https://www.facebook.com/{v}/dialog/oauth/";

    
    /**
     * Class constructor.
     * 
     * Passing a user id is not required to act as the user, you're only required to specify the access token.
     * 
     * @param string       $appId   Application's client ID
     * @param string       $secret  Application's client secret
     * @param array|object $access  [ token, expires, me ] or { 'token': string, 'expires': unixtime, 'user': me }
     */
    public function __construct($clientId, $clientSecret, $access=null)
    {
        $this->setCredentials($clientId, $clientSecret);
        $this->setAccessInfo($access);
        
        $this->curl_opts[CURLOPT_HTTPHEADER] = array('Expect:', 'Content-Type:', 'Content-Length:');
    }


    /**
     * Fetch the OAuth2 access token.
     * 
     * @param array  $params  Parameters
     * @return object
     */
    protected function fetchAccessToken(array $params)
    {
        $response = $this->get('oauth/access_token', $params);
 
        parse_str($response, $data);
        return $data ? (object)$data : $response;
    }
    
    /**
     * Request a new access token with an extended lifetime of 60 days from now.
     * @link https://developers.facebook.com/docs/facebook-login/access-tokens/#extending
     *
     * @return object  { 'token': string, 'expires': unixtime }
     */
    public function extendAccess()
    {
        if (!isset($this->accessToken)) throw new Exception("Unable to extend access token. Access token isn't set.");
        $response = $this->get("oauth/access_token", ['client_id'=>$this->clientId,
            'client_secret'=>$this->clientSecret, 'grant_type'=>'fb_exchange_token',
            'fb_exchange_token'=>$this->getAccessToken()]);
        
        parse_str($response, $data);
        if (reset($data) == '') $data = json_decode($response, true);

        if (empty($data) || !isset($data['access_token'])) {
            $error = isset($data['error']['message']) ? $data['error']['message'] : $response;
            throw new Exception("Failed to extend the access token: {$error}");
        }
        
        $this->accessToken = $data['access_token'];
        $this->accessExpires = time() + $data['expires'];
        
        return $this->getAccessInfo();
    }

    /**
     * Check if the authenticated user has given the requested permissions.
     * If the user doesn't have these permissions, redirect him back to the auth dialog.
     *
     * @return Connection $this
     */
    public function checkScope()
    {
        $permissions = array_keys(array_filter((array)$this->get('me/permissions')->data[0]));

        if (array_diff($this->scope, $permissions)) {
            $this->accessToken = null;
            return $this->auth($this->scope);
        }

        return $this; 
    }

    
    /**
     * Get the authenticated user
     * 
     * @return Me
     */
    public function me()
    {
        $fields = 'id,link,name,first_name,last_name,gender,birthday,locale,email,website,location';

     	$data = $this->get('me', compact('field'));
        return new User($data);
    }
}

