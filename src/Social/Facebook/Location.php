<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Facebook;

use Social\Common\Country, Social\Common\CountrySubdivision;

/**
 * Entity representing a location
 */
class Location implements \Social\Location
{
    /** @var string */
    protected $_city;
    
    /** @var string */
    protected $_state;
    
    /** @var string */
    protected $_country;
    
    /**
     * Class constructor
     * 
     * @param object $data
     */
    public function __construct($data) {
        foreach ($data as $key => $value) $this->$key = $value;
    }
    
    /**
     * Cast so of the data to entities
     */
    protected function cast()
    {
        $parts = explode(', ', $this->name, 3);
        
        $this->_country = new Country(end($parts));
        if (count($parts) > 1) $this->_city = $parts[0];
        if (count($parts) > 2) $this->_state = new CountryRegion($this->_country, $parts[1]);
    }
    
    /**
     * Get street name and number
     * 
     * @return string
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
    public function getPostalcode()
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
     * @return \Social\CountrySubdivision
     */
    public function getState($format=null)
    {
        if (!isset($this->_state) || !$this->_state->isRequired()) return null;
        return $this->getRegion($format);
    }
    
    /**
     * Get state, province or region
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return \Social\CountrySubdivision
     */
    public function getRegion($format=null)
    {
        return isset($this->_state) ? $this->_state->getFormatted($format) : null;
    }
    
    /**
     * Get country.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return \Social\Country|string
     */
    public function getCountry($format=null)
    {
        return $this->_country->getFormatted($format);
    }
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
