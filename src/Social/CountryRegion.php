<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * Common interface for an entity representing a state, province or region.
 */
interface CountryRegion
{
    /**
     * Get country
     * 
     * @return Country
     */
    public function getCountry();
    
    
    /**
     * Get ISO 3166-2 country subdivision code
     * 
     * @return string
     */
    public function getCode();
    
    /**
     * Get country name
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Returns true if subdivision is required for country
     * 
     * @return boolean
     */
    public function isRequired();
    
    
    /**
     * Get country name, code or object.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return \Social\Country|string
     */
    public function getFormatted($format);
}
