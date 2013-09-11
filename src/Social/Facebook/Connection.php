<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Facebook;

use Social\Connection as Base;
use Social\Collection;

/**
 * Facebook Graph API connection.
 * @see http://developers.facebook.com/docs/reference/api/
 * @package Facebook
 * 
 * Before you start register your application at https://developers.facebook.com/apps and retrieve a client ID and
 *  secret.
 */
class Connection extends Base implements \Social\Auth
{
    use \Social\OAuth2;
    
    /**
     * Name of the API service
     */
    const serviceProvider = 'facebook';
    
    /**
     * Facebook Open Graph API URL
     */
    const apiURL = "https://graph.facebook.com/";
    
    /**
     * Facebook authentication URL
     */
    const authURL = "https://www.facebook.com/dialog/oauth";

    
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
     * @see https://developers.facebook.com/docs/facebook-login/access-tokens/#extending
     *
     * @return object  { 'token': string, 'expires': unixtime }
     */
    public function extendAccess()
    {
        if (!isset($this->accessToken)) throw new Exception("Unable to extend access token. Access token isn't set.");
        $response = $this->httpRequest('GET', "oauth/access_token", ['client_id'=>$this->appId,
            'client_secret'=>$this->appSecret, 'grant_type'=>'fb_exchange_token',
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
     */
    public function me()
    {
        return $this->get('me');
    }

    
    /**
     * Convert data to Entity, Collection or DateTime.
     * 
     * @todo fix this
     * 
     * @param mixed   $data
     * @param string  $type    Entity type
     * @param boolean $stub    If an Entity, asume it's a stub
     * @param object  $source  { 'url': string, 'params': array }
     * @return Entity|Collection|DateTime|mixed
     */
    public function convert($data, $type=null, $stub=true, $source=null)
    {
        // Don't convert
        if ($data instanceof Entity || $data instanceof Collection || $data instanceof \DateTime) {
            return $data;
        }
        
        // Scalar
        if (is_scalar($data) || is_null($data)) {
            if (preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d$/', $data)) return new \DateTime($data);
            if (isset($type)) return $this->stub($type, $data);
            return $data;
        }

        // Entity
        if ($data instanceof \stdClass && isset($data->id)) return new Entity($this, $type, $data, $stub);
           
        // Collection
        if ($data instanceof \stdClass && isset($data->data) && is_array($data->data)) {
            if (is_string($source)) $source = $this->extractParams($source);
            if (isset($data->paging->next)) // Make sure the same parameters are used in the next query
                $data->paging->next = $this->buildUrl($data->paging->next, (array)$source, false);
            return new Collection($this, $type, $data->data, isset($data->paging->next) ? $data->paging->next : null);
        }
        
        // Array or value object
        if (is_array($data) || $data instanceof \stdClass) {
            foreach ($data as &$value) {
                $value = $this->convertData($value, $type);
            }
            return $data;
        }
        
        // Probably some other kind of object
        return $data;
    }
}
