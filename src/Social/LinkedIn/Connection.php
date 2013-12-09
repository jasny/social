<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\LinkedIn;

use Social\Connection as Base;

/**
 * LinkedIn API connection.
 * @see http://developers.linkedin.com/docs/api/reference
 * @package LinkedIn
 * 
 * Before you start register your application at http://linkedin.com/you/apps/ and retrieve a client ID and secret
 */
class Connection extends Base implements \Social\Auth
{
    use \Social\OAuth2;
    
    /**
     * Name of the API's service provider
     */
    const serviceProvider = 'linkedin';
    
    /**
     * LinkedIn API URL
     */
    const apiURL = "https://api.linkedin.com/v1/";
    
    /**
     * LinkedIn authentication URL
     */
    const authURL = "https://www.linkedin.com/uas/oauth2/authorization";
    
    
    /**
     * Class constructor.
     * 
     * Passing a user id is not required to act as the user, you're only required to specify the access token.
     * 
     * @param string        $appId          Application's client ID
     * @param string        $secret         Application's client secret
     * @param array|object  $access         [ user's access token, expire timestamp, LinkedIn id ] or { 'token': string, 'expires': unixtime, 'user': LinkedIn id }
     */
    public function __construct($clientId, $clientSecret, $access=null)
    {
        $this->curl_opts[CURLOPT_HTTPHEADER]['x-li-format'] = 'json';
        
        $this->setCredentials($clientId, $clientSecret);
        $this->setAccessInfo($access);
    }

    /**
     * Initialise an HTTP request object.
     *
     * @param object|string $request  url or value object
     * @return object
     */
    protected function initRequest($request)
    {
        $request = parent::initRequest($request);
        
        if ($this->accessToken) $request->queryParams['oauth2_access_token'] = $this->accessToken;
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
	var_dump($result);

        if (is_object($result) && isset($result->message)) return $result->message;
        return parent::httpError($httpcode, $result);
    }

    /**
     * Fetch the OAuth2 access token.
     * 
     * @param array  $params  Parameters
     * @return object
     */
    protected function fetchAccessToken(array $params)
    {
        return $this->post(dirname(static::authURL) . '/accessToken', $params);
    }
    

    /**
     * Get current user profile.
     * 
     * @return Me
     */
    public function me()
    {
        return new Me($this->get('people/~:(' . join(',', Me::$fields) . ')'));
    }
}
