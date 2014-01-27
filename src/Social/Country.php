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
 * Common interface for an entity representing a country.
 */
interface Country
{
    /**
     * Get ISO 3166-1 country code
     * 
     * @return string
     */
    public function getCode();
    
    /**
     * Get country name.
     * The name is normalized (using Jasny\ISO\Countries).
     * 
     * @return string
     */
    public function getName();
    
    
    /**
     * Get country name, code or object.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return \Social\Country|string
     */
    public function getFormatted($format);
}
