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
 * Common interface for an entity representing a person.
 */
interface Person
{
    /**
     * Get person's full name
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Get person's first name
     * 
     * @return string
     */
    public function getFirstName();
    
    /**
     * Get person's last name
     * 
     * @return string
     */
    public function getLastName();
    
    
    /**
     * Get person's gender
     * 
     * @return string  'male' or 'female'
     */
    public function getGender();
    
    /**
     * Get person's date of birth
     * 
     * @return string
     */
    public function getDateOfBirth();
    
    
    /**
     * Get url to person's picture.
     * 
     * If size is omited the largest available image is returned.
     * $size is an just an indication, the image may not have those exact dimensions.
     * 
     * @param string $size   width x height (eg '800x600' or 'x400')
     * @return string
     */
    public function getPicture($size=null);
    
    /**
     * Get url to person's locale (= language)
     * 
     * @return string
     */
    public function getLocale();
    
    /**
     * Get person's location
     * 
     * @return Location
     */
    public function getLocation();
    
    
    /**
     * Get person's email address
     * 
     * @return string
     */
    public function getEmail();
    
    /**
     * Get person's website.
     * Return the main (or first) website, if multiple are known.
     * 
     * @return string
     */
    public function getWebsite();
    
    
    /**
     * Get information about person's employment
     * 
     * @return Employment
     */
    public function getEmployment();
    
    /**
     * Get person's employment company.
     * Shortcut for <code>$person->getEmployment()->getCompany()</code>
     * 
     * @return Company
     */
    public function getCompany();
    
    /**
     * Get person's bio
     * 
     * @return string
     */
    public function getDescription();
}
