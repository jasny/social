<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Google;

/**
 * Connection for the Google API's.
 * @link http://developers.Google.com/docs/api/reference
 * @package Google
 * 
 * Before you start register your application at https://code.google.com/apis/console/#access and retrieve a client ID
 *  and secret. You might also need to enable services at https://code.google.com/apis/console/ and retrieve an API key.
 */
class Connection extends Base
{
    /**
     * Get a connection to one of the Google APIs.
     * @link https://developers.google.com/apis-explorer/
     * 
     * Specifying the version is recommended. If you omit the version, the preferred version will be fetched using the
     * discovery service.
     * 
     * @param string $name     API name
     * @param string $version  API version
     * @param string $auth     Authentication method 'key' or 'oauth2'
     * @return Connection
     */
    public function api($name, $version=null, $auth='oauth2')
    {
        if (!isset($version)) {
            $apis = $this->get(self::discoveryURL . 'apis', 
                ['name'=>$name, 'preferred'=>true, 'fields'=>'items/version'], false);
            
            if (empty($apis->items)) throw new \Exception("This Google $name API is not available");
            $version = $apis->items[0]->version;
        }
        
        $connection = strtolower($auth) == 'key' ?
            new static($this->apiKey) :
            new static($this->apiKey, $this->clientId, $this->clientSecret, $this->getAccessInfo());
        $connection->apiName = $name;
        $connection->apiVersion = $version;
        
        return $connection;
    }
    
    /**
     * Discover this API.
     * @link https://developers.google.com/discovery/
     * 
     * @return object
     */
    public function discover()
    {
        return $this->get(self::discoveryURL . "apis/{$this->apiName}/{$this->apiVersion}/rest", false);
    }
    
    
    /**
     * Get the Google Maps API
     * 
     * @return \Social\GoogleMaps\Connection
     */
    public function maps()
    {
        return new \Social\GoogleMaps\Connection($this->apiKey);
    }
    
    /**
     * Get the Google+ API
     * 
     * @return \Social\GooglePlus\Connection
     */
    public function plus()
    {
        return new \Social\GooglePlus\Connection($this->apiKey, $this->clientId, $this->clientSecret,
            $this->getAccessInfo());
    }
    
    /**
     * Get the YouTube API
     * 
     * @return \Social\YouTube\Connection
     */
    public function youtube()
    {
        return new \Social\YouTube\Connection($this->apiKey, $this->clientId, $this->clientSecret,
            $this->getAccessInfo());
    }
    
    /**
     * Get the Freebase API
     * 
     * @return \Social\Freebase\Connection
     */
    public function freebase()
    {
        return new \Social\Freebase\Connection($this->apiKey, $this->clientId, $this->clientSecret,
            $this->getAccessInfo());
    }
}
