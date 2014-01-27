<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\LinkedIn;

use Social\Common\Country, Social\Common\Region;

/**
 * Entity representing a an address or profile location
 */
class Address implements \Social\Location
{
    /**
     * Class constructor
     * 
     * @param object|array|string $data  Address or location
     */
    public function __construct($data) {
        foreach ($data as $key => $value) $this->$key = $value;
        
        $this->cast();
    }
    
    /**
     * Cast some data to entities
     */
    protected function cast()
    {
        if (isset($this->country)) $this->country = new Country($this->country);
        if (isset($this->state)) $this->state = new Region($this->state);
    }
    
    
    /**
     * Get street name and number
     * 
     * @return string
     */
    public function getAddress()
    {
        return isset($this->street1) ? $this->street1 : null;
    }
    
    /**
     * Get postal code / zip code
     * 
     * @return string
     */
    public function getPostalcode()
    {
        return isset($this->postalCode) ? $this->postalCode : null;
    }
    
    /**
     * Get city
     * 
     * @return string
     */
    public function getCity()
    {
        return isset($this->city) ? $this->city : null;
    }
    
    /**
     * Get state, province or region.
     * Only for countries that require this in their mailing address.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return Region|string
     */
    public function getState($format=null)
    {
        return isset($this->state) ? $this->state->getFormatted($format) : null;
    }
    
    /**
     * Get state, province or region
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return Region|string
     */
    public function getRegion($format=null)
    {
        if (isset($this->state)) return $this->state->getFormatted($format);
        
        if (isset($this->regionCode)) {
            $region = new Region($this->regionCode);
            return $region->getFormatted($format);
        }
        return null;
    }
    
    /**
     * Get country.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return Country|string
     */
    public function getCountry($format=null)
    {
        $country = isset($this->country) ?
            $this->country :
            (isset($this->countryCode) ? new Country($this->countryCode) : null);
        
        return isset($country) ? $country->getFormatted($format) : null;
    }
    
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        if (isset($this->name)) return $this->name;
        
        return (string)join(', ', array_filter([$this->getAddress(), $this->getCity(), $this->getState(),
                $this->getCountry()]));
    }
}
