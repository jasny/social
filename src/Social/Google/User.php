<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Google;

/**
 * Entity representing a user
 * 
 * @package Google
 */
class User extends \Social\Entity implements \Social\User
{
    /**
     * Return user ID
     * 
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Get user's full name
     * 
     * @return string
     */
    public function getName()
    {
        if (isset($this->name)) return $this->name;
        return join(' ', array_filter([$this->getFirstName(), $this->getLastName()]));
    }
    
    /**
     * Get user's first name
     * 
     * @return string
     */
    public function getFirstName()
    {
        return isset($this->given_name) ? $this->given_name : null;
    }
    
    /**
     * Get user's last name
     * 
     * @return string
     */
    public function getLastName()
    {
        return isset($this->family_name) ? $this->family_name : null;
    }
    
    
    /**
     * Get user's gender
     * 
     * @return string
     */
    public function getGender()
    {
        return isset($this->gender) ? $this->gender : null;
    }
    
    /**
     * Get user's date of birth
     * 
     * @return string
     */
    public function getDateOfBirth()
    {
        return null;
    }
    
    /**
     * Get url to user's locale (= language)
     * 
     * @return string
     */
    public function getLocale()
    {
        return isset($this->locale) ? $this->locale : null;
    }
    
    /**
     * Get user's timezone
     * 
     * @return null
     */
    public function getTimezone()
    {
        return null;
    }
    
    
    /**
     * Get username on Facebook.
     * 
     * @return string
     */
    public function getUsername()
    {
        return isset($this->email) ? $this->email : null;
    }
    
    /**
     * Get URL to profile
     * 
     * @return string
     */
    public function getLink()
    {
        return isset($this->link) ? $this->link : null;
    }
    
    /**
     * Get url to profile picture.
     * 
     * If size is omited the largest available image is returned.
     * $size is an just an indication, the image may not have those exact dimensions.
     * 
     * @param string $size   'square', 'small', 'medium', 'large' or {width}x{height} (eg '800x600' or 'x400')
     * @return string
     */
    public function getPicture($size=null)
    {
        return isset($this->picture) ? $this->picture : null;
    }
    
    
    /**
     * Get user's email address
     * 
     * @return string
     */
    public function getEmail()
    {
        return isset($this->email) ? $this->email : null;
    }
    
    /**
     * Get user's website.
     * Return the main (or first) website, if multiple are known.
     * 
     * @return string
     */
    public function getWebsite()
    {
        return null;
    }
    
    /**
     * Get user's address
     * 
     * @return null
     */
    public function getLocation()
    {
        return null;
    }
    
    
    /**
     * Get user's bio
     * 
     * @return null
     */
    public function getDescription()
    {
        return null;
    }
    
    /**
     * Get user's employment
     * 
     * @return null
     */
    public function getEmployment()
    {
        return null;
    }
    
    /**
     * Get user's employment company.
     * 
     * @return null
     */
    public function getCompany()
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
        return $this->getName() ?: (string)$this->getUsername();
    }
}
