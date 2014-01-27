<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\PostcodeAPI;

use Social\Connection as Base;

/**
 * Postcode API Connection (for Dutch postalcodes).
 * @link http://www.postcodeapi.nu
 * @package PostcodeAPI
 * 
 * Before you start request an API key at http://www.postcodeapi.nu/#request.
 */
class Connection extends Base
{
    /**
     * URL to the api
     */
    const apiURL = "http://api.postcodeapi.nu/";
    
    /**
     * Class constructor
     * 
     * @param string $key  API key
     */
    public function __construct($key)
    {
        $this->key = $key;
        $this->curl_opts[CURLOPT_HTTPHEADER]['Api-Key'] = $key;
    }
    
    /**
     * GET from the Postcode API.
     * 
     * @param string|array $resource  One or more postcodes, addresses or lat/lng
     * @param array        $params
     * @return object|array
     */
    public function get($resource, array $params=[])
    {
        // Multiple requests
        if (is_array($resource) && is_int(key($resource))) {
            $requests = [];
            foreach ($resource as $r) {
                $requests[] = (object)['method'=>'GET', 'url'=>$r, 'params'=>$params];
            }
            
            return $this->request($requests);
        }
        
        // Single request
        return parent::get($resource, $params);
    }
}
