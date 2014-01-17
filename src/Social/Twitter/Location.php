<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Twitter;

/**
 * Data representing a location.
 */
class Location implements \Social\Location
{
    protected $_city;
    
    /**
     * Class constructor
     * 
     * @param string $city
     */
    public function __construct($city)
    {
        $this->_city = $city;
    }
    

    /**
     * Get street name and number
     * 
     * @return null
     */
    public function getAddress()
    {
        return null;
    }
    
    /**
     * Get postal code / zip code
     * 
     * @return string
     */
    public function getPostalCode()
    {
        return null;
    }
    
    /**
     * Get city
     * 
     * @return string
     */
    public function getCity()
    {
        return $this->_city;
    }
    
    /**
     * Get state, province or region.
     * Only for countries that require this in their mailing address.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return null
     */
    public function getState($format=null)
    {
        return null;
    }
    
    /**
     * Get state, province or region
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return null
     */
    public function getRegion($format=null)
    {
        return null;
    }
    
    /**
     * Get country.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return null
     */
    public function getCountry($format=null)
    {
        return null;
    }
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->_city;
    }
}
