<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\GoogleMaps;

use Social\Connection as Base;

/**
 * Connection to Google Maps API Web Services:
 *  - Directions API
 *  - Distance Matrix API
 *  - Elevation API
 *  - Geocoding API
 *  - Time Zone API
 *  - Places API
 * 
 * @see https://developers.google.com/maps/documentation/webservices/
 * @package GoogleMaps
 * 
 * For the Places API you need to enable the service at https://code.google.com/apis/console/ and retrieve an API key.
 */
class Connection extends Base
{
    /**
     * Name of the API's service provider
     */
    const serviceProvider = 'google-maps';
    
    /**
     * Google Maps API URL
     */
    const apiURL = "https://maps.googleapis.com/api/";
    
    
    /**
     * Class constructor.
     * 
     * @param string $apiKey  Application's API key
     */
    public function __construct($apiKey=null)
    {
        if (isset($apiKey)) $this->queryParams['key'] = $apiKey;
        $this->queryParam['sensor'] = false;
    }
    
    /**
     * Get the application's API key
     * 
     * @return string
     */
    public function getApiKey()
    {
        return isset($this->queryParams['key']) ? $this->queryParams['key'] : null;
    }
    
    /**
     * Initialise an HTTP request object.
     *
     * @param object|string $request  value object or url
     * @return object
     */
    protected function initRequest($request)
    {
        parent::initRequest($request);

        $output = basename(parse_url($request->url, PHP_URL_PATH));
        if ($output != 'json' && $output != 'xml') {
            list($url, $query) = explode('?', $request->url, 2) + [1=>null];
            $request->url = $url . '/json' . ($query ? "?$query" : '');
        }
        
        return $request;
    }
}
