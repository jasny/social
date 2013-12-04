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
 * Common interface for an entity representing a company or office.
 */
interface Company
{
    /**
     * Get company's full name
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Get url to company's logo.
     * 
     * If size is omited the largest available image is returned.
     * $size is an just an indication, the image may not have those exact dimensions.
     * 
     * @param string $size   width x height (eg '800x600' or 'x400')
     * @return string
     */
    public function getPicture($size=null);
    
    
    /**
     * Get company's email address
     * 
     * @return string
     */
    public function getEmail();
    
    /**
     * Get company's website.
     * Return the main (or first) website, if multiple are known.
     * 
     * @return string
     */
    public function getWebsite();
    
    /**
     * Get company's description
     * 
     * @return string
     */
    public function getDescription();
    
    /**
     * Get company's location
     * 
     * @return Location
     */
    public function getLocation();
}
