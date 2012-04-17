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
     * @return Entity
     */
    public function get($id, array $params=array())
    {
        $data = $this->getData($id, $params);
	return $data;
//        return $this->convertData($data, $params + $this->extractParams($id));
    }
    
    
    /**
     * Convert data to Entity, Collection or DateTime.
     * 
     * @param mixed $data
     * @param array $params  Parameters used to fetch data
     * @return Entity|Collection|DateTime|mixed
     */
    public function convertData($data, array $params=array())
    {
        // Don't convert
        if ($data instanceof Entity || $data instanceof Collection || $data instanceof \DateTime) {
            return $data;
        }
        
        // Scalar
        if (is_scalar($data) || is_null($data)) {
            if (preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d$/', $data)) return new \DateTime($data);
            return $data;
        }

        // Entity
        if ($data instanceof \stdClass && isset($data->id)) return new Entity($this, null, $data, true);
           
        // Collection
        /*if ($data instanceof \stdClass && isset($data->data) && is_array($data->data)) {
            $nextPage = isset($data->paging->next) ? $data->paging->next = $this->buildUrl($data->paging->next, $params, false) : null; // Make sure the same parameters are used in the next query
            return new Collection($this, $data->data, $nextPage);
        }*/
        
        // Array or value object
        if (is_array($data) || $data instanceof \stdClass) {
            foreach ($data as &$value) {
                $value = $this->convertData($value);
            }
            return $data;
        }
        
        // Probably some other kind of object
        return $data;
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
    }}