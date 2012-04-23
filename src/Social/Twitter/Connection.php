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
    public function getAuthUrl($level='authorize', $callbackUrl=null, &$tmp_access=null)
    {
        $callbackUrl = $this->getCurrentUrl($callbackUrl, array('twitter_auth' => 'auth'));
        return parent::getAuthUrl($level, $callbackUrl, $tmp_access);
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
     * @param array  $params  Get parameters
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
     * @param array  $params
     * @return object
     */
    public function get($id, array $params=array())
    {
        $data = $this->getData($id, $params);
        return $data;
    }

    /**
     * Get the current user info
     * 
     * @return object
     */
    public function me()
    {
        if (!isset($this->me)) $this->me = $twitter->get('account/verify_credentials');
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