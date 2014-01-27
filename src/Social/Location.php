<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social;

/**
 * Common interface for an entity representing an address.
 */
interface Location
{
    /**
     * Get street name and number
     * 
     * @return string
     */
    public function getAddress();
    
    /**
     * Get postal code / zip code
     * 
     * @return string
     */
    public function getPostalcode();

    /**
     * Get city
     * 
     * @return string
     */
    public function getCity();
    
    /**
     * Get state, province or region.
     * Only for countries that require this in their mailing address.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return CountryState|string
     */
    public function getState($format=null);
    
    /**
     * Get state, province or region
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return CountryState|string
     */
    public function getRegion($format=null);
    
    /**
     * Get country.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return Country|string
     */
    public function getCountry($format=null);
}
