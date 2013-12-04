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
 * Common interface for an entity representing the employment of a person at a company.
 */
interface Employment
{
    /**
     * Get job title
     * 
     * @return string
     */
    public function getJobTitle();
    
    /**
     * Get (long) job description
     * 
     * @return string
     */
    public function getJobDescription();
    
    
    /**
     * Get postal code / zip code
     * 
     * @return Company
     */
    public function getCompany();
    
    /**
     * Get address of the company.
     * Shortcut to Employment->getCompany()->getLocation().
     * 
     * @return Address
     */
    public function getLocation();
    
    
    /**
     * Get date when first employed
     * 
     * @return \DateTime
     */
    public function getStartDate();
    
    /**
     * Get date when last employed
     * 
     * @return \DateTime
     */
    public function getEndDate();
}
