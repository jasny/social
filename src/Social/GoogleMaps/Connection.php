<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
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
 * @link https://developers.google.com/maps/documentation/webservices/
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
    const apiURL = "https://maps.googleapis.com/maps/api/";
    
    
    /**
     * Class constructor.
     * 
     * @param string $apiKey  Application's API key
     */
    public function __construct($apiKey=null)
    {
        if (isset($apiKey)) $this->queryParams['key'] = $apiKey;
        $this->queryParams['sensor'] = 'false';
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
    
    /**
     * Build a HTTP query, converting arrays to a comma seperated list and removing null parameters.
     * 
     * @param type $params
     * @return string
     */
    protected static function buildHttpQuery($params)
    {
        if (isset($params['components']) && is_array($params['components'])) {
            $components = [];
            foreach ($params['components'] as $key=>$value) $components[] = "$key:$value";
            $params['components'] = join('|', $components);
        }
        
        return parent::buildHttpQuery($params);
    }
    
    
    /**
     * Calculate directions between locations.
     * 
     * @param string $origin       Address or object with lat/lon
     * @param string $destination  Address or object with lat/lon
     * @param array  $params       Additional parameters
     * @return object
     */
    public function directions($origin, $destination, array $params=[])
    {
        if (!is_scalar($origin)) {
            $latlng = (array)$origin;
            $latitude = @$latlng['lat'] ?: $latlng['latitude'];
            $longitute = @$latlng['lon'] ?: @$latlng['lng'] ?: $latlng['longitute'];            
            $origin = "$latitude,$longitute";
        }
        
        if (!is_scalar($destination)) {
            $latlng = (array)$destination;
            $latitude = @$latlng['lat'] ?: $latlng['latitude'];
            $longitute = @$latlng['lon'] ?: @$latlng['lng'] ?: $latlng['longitute'];            
            $destination = "$latitude,$longitute";
        }
        
        $ret = $this->get('directions', compact('origin', 'destination') + $params);
        return $ret ? $ret->results[0] : null;
    }
    
    /**
     * Find latitude, longitude and address components based on address.
     * @link https://developers.google.com/maps/documentation/geocoding/
     * 
     * @param string|array $address  Address or associated array with address components  
     * @param array        $params   Additional parameters
     * @return object
     */
    public function geocode($address, array $params=[])
    {
        if (is_array($address)) {
            $params['components'] = (array)$address;
            $address = @$components['address'];
            unset($params['components']['address']);
        }
        
        $ret = $this->get('geocode', compact('address') + $params);
        return $ret ? $ret->results[0] : null;
    }
    
    /**
     * Find address components based on latitude and longitude
     * 
     * @param string|array|object $latlng  Array with keys 'lat' or 'latitide' and 'lon', 'lng' or 'longitute'.
     * @param array               $params
     */
    public function reverseGeocode($latlng, array $params=[])
    {
        if (!is_scalar($latlng)) {
            $latlng = (array)$latlng;
            $latitude = @$latlng['lat'] ?: $latlng['latitude'];
            $longitute = @$latlng['lon'] ?: @$latlng['lng'] ?: $latlng['longitute'];
            $latlng = "$latitude,$longitute";
        }
        
        $ret = $this->get('geocode', compact('latlng') + $params);
        return $ret ? $ret->results[0] : null;
    }
}
