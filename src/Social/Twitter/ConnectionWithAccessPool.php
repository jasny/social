<?php
/**
 * Twitter API connection that uses multiple access tokens/secrets.
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Exception;

/**
 * Twitter API connection that uses multiple access tokens/secrets.
 * The class uses round robin to select a different token for each request.
 */
class ConnectionWithAccessPool extends Connection
{
    /**
     * Pool with access tokens and secrets
     * @var array 
     */
    protected $accessPool;
    
    /**
     * Class constructor.
     * 
     * Passing $user is not required to act as the user, you're only required to specify the access token and secret.
     * 
     * @param string $consumerKey     Application's consumer key
     * @param string $consumerSecret  Application's consumer secret
     * @param array  $accessPool      Array with value objects { 'token': string, 'secret': string }
     */
    public function __construct($consumerKey, $consumerSecret, array $accessPool)
    {
        parent::__construct($consumerKey, $consumerSecret);
        
        $this->accessPool = new ArrayIterator($accessPool);
        
        // Use the first token for next request
        $access = (object)$this->accessPool->current();
        $this->accessToken = $access->token;
        $this->accessToken = $access->secret;
    }
    
    /**
     * Switch to the next access token 
     */
    protected function nextAccessToken()
    {
        $this->accessPool->next();
        if (!$this->accessPool->valid()) $this->accessPool->rewind();
        
        $access = (object)$this->accessPool->current();
        $this->accessToken = $access->token;
        $this->accessToken = $access->secret;
    }
    
    /**
     * Get Authentication header.
     * 
     * @param string $method  GET, POST or DELETE
     * @param string $url
     * @param array  $params  Request parameters
     * @param array  $oauth   Additional/Alternative oAuth values
     * @return string
     */
    protected function getAuthorizationHeader($method, $url, $params, array $oauth=array())
    {
        $header = parent::getAuthorizationHeader($method, $url, $params, $oauth);
        $this->nextAccessToken();
        
        return $header;
    }
    
    /**
     * Get current user profile.
     * 
     * @return Me
     */
    public function me()
    {
        throw new Exception("Unable to get Me entity, since the current user changes as the access tokens rotate.");
    }    
}