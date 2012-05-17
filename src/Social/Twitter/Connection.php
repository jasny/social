<?php
/**
 * Twitter API connection.
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\OAuth1;
use Social\Exception;

/**
 * Twitter API connection.
 * @see https://dev.twitter.com/docs
 * 
 * Before you start, register your application at https://dev.twitter.com/apps and retrieve a custumor key and consumer secret.
 * 
 * @package Social
 * @subpackage Twitter
 */
class Connection extends OAuth1
{
    /**
     * Twitter API URL
     */
    const baseURL = "https://api.twitter.com/1/";

    
    /**
     * Current user
     * @var Entity
     */
    protected $me;
    
    /**
     * Get Twitter API URL.
     * 
     * @return string
     */
    protected function getBaseUrl()
    {
        return self::baseURL;
    }    

    /**
     * Get authentication url.
     * Temporary accesss information is automatically stored to a session.
     *
     * @param int    $level        'authorize' = read/write on users behalf, 'authenticate' = login + user info only
     * @param string $callbackUrl  The URL to return to after successfully authenticating.
     * @param object $access       Will be filled with the temporary access information.
     * @return string
     */
    public function getAuthUrl($level='authenticate', $callbackUrl=null, &$access=null, $accessSecret=null)
    {
        $callbackUrl = $this->getCurrentUrl($callbackUrl, array('twitter_auth' => $level));
        return parent::getAuthUrl($level, $callbackUrl, $access, $accessSecret);
    }
    
    /**
     * Get the URL of the current script.
     *
     * @param string $page    Relative path to page
     * @param array  $params
     * @return string
     */
    static public function getCurrentUrl($page=null, array $params=array())
    {
        if (!isset($params['twitter_auth'])) $params['twitter_auth'] = null;
        $params['oauth_token'] = null;
        $params['oauth_verifier'] = null;

        return parent::getCurrentUrl($page, $params);
    }

    
    /**
     * Fetch raw data from Twitter.
     * 
     * @param string $id
     * @param array  $params  GET parameters
     * @return array
     */
    public function getData($id, array $params=array())
    {
        $response = $this->httpRequest('GET', "$id.json", $params);
        $data = json_decode($response);
        return $data ?: $response;
    }
    
    /**
     * Fetch an entity (or other data) from Twitter.
     * 
     * @param string $id
     * @param array  $params  GET parameters
     * @return object
     */
    public function get($id, array $params=array())
    {
        $data = $this->getData($id, $params);
        return $data;
    }

    /**
     * POST to Twitter.
     * 
     * @param string $id
     * @param array  $params  POST parameters
     * @return array
     */
    public function post($id, array $params=array())
    {
        $response = $this->httpRequest('POST', "$id.json", $params);
        $data = json_decode($response);
        return $data ?: $response;
    }
    
    /**
     * Stream content from Twitter.
     * 
     * @param string   $id
     * @param callback $writefunction  Stream content to this function
     * @param array    $params         Request parameters
     * @return boolean
     */
    public function stream($id, $writefunction, array $params=array())
    {
        $method = $id == 'statuses/filter' ? 'POST' : 'GET';
        
        switch ($id) {
            case 'user': $url = "https://userstream.twitter.com/2/user.json"; break;
            case 'site': $url = "https://sitestream.twitter.com/2b/site.json"; break;
            default:     $url = "https://stream.twitter.com/1/$id.json"; break;
        }
        
        $response = $this->httpRequest($method, $url, $params, array(), array(), $writefunction);
        return $response;
    }
    
    /**
     * Get the current user info
     * 
     * @return object
     */
    public function me()
    {
        if (!isset($this->me)) $this->me = $this->get('account/verify_credentials');
        return $this->me;
    }
    
    
    /**
     * Serialization
     * { @internal Don't serialze cached objects }}
     * 
     * @return array
     */
    public function __sleep()
    {
        return array('appId', 'appSecret', 'accessToken', 'accessExpires', 'accessTimestamp');
    }
}