<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Common;

/**
 * Data representing a locaion or address.
 */
class Location implements \Social\Location
{
    /**
     * Class constructor
     * 
     * @param array|object $data  Address properties
     */
    public function __construct($data)
    {
        foreach ($data as $key=>$value) {
            $this->$key = $value;
        }
        
        $this->cast();
    }
    
    /**
     * Cast some data to entities
     */
    protected function cast()
    {
        if (isset($this->country) && !$this->country instanceof \Social\Country)
            $this->country = new Country($this->country);
        
        if (isset($this->state) && !$this->state instanceof \Social\CountrySubdivision)
            $this->state = new CountryRegion($this->country, $this->state);
    }


    /**
     * Get street name and number
     * 
     * @return string
     */
    public function getAddress()
    {
        return isset($this->address) ? $this->address : null;
    }
    
    /**
     * Get postal code / zip code
     * 
     * @return string
     */
    public function getPostalCode()
    {
        return isset($this->postal_code) ? $this->postal_code : null;
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
     * @return \Social\CountrySubdivision
     */
    public function getState($format=null)
    {
        if (!isset($this->state) || !$this->state->isRequired()) return null;
        return $this->state->getFormatted($format);
    }
    
    /**
     * Get state, province or region
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return \Social\CountrySubdivision
     */
    public function getRegion($format=null)
    {
        return isset($this->state) ? $this->state->getFormatted($format) : null;
    }
    
    /**
     * Get country.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return \Social\Country|string
     */
    public function getCountry($format=null)
    {
        return isset($this->country) ? $this->country->getFormatted($format) : null;
    }
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return (string)join(', ', array_filter([$this->getAddress(), $this->getCity(), $this->getState(),
            $this->getCountry()]));
    }
}
