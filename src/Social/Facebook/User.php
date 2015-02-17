<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Facebook;

/**
 * Entity representing a user
 * 
 * @package Facebook
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
     * Get username on Facebook.
     * Returns the ID is the username is unknown.
     * 
     * @return string
     */
    public function getUsername()
    {
        if (isset($this->username)) return $this->username;
        if (isset($this->link)) return parse_url($this->link, PHP_URL_PATH);
        return $this->id;
    }
    
    /**
     * Get URL to profile on Facebook
     * 
     * @return string
     */
    public function getLink()
    {
        if (isset($this->link)) return $this->link;
        return 'http://www.facebook.com/' . $this->getUsername();
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
        if (!isset($this->id) && !isset($this->username)) return null;
        
        if (!isset($size)) $size = '8192x';
        if (strpos($size, 'x') !== false) {
            list($width, $height) = explode('x', $size);
            $query = "width=$width&height=$height";
        } else {
            $query = "type=$size";
        }
        
        return "http://graph.facebook.com/" . $this->getUsername() . "/picture?$query";
    }

    
    /**
     * Get user's full name
     * 
     * @return string
     */
    public function getName()
    {
        return isset($this->name) ? $this->name : null ?: join(' ', [$this->first_name, $this->last_name]);
    }
    
    /**
     * Get user's first name
     * 
     * @return string
     */
    public function getFirstName()
    {
        return isset($this->first_name) ? $this->first_name : null;
    }
    
    /**
     * Get user's last name
     * 
     * @return string
     */
    public function getLastName()
    {
        return isset($this->last_name) ? $this->last_name : null;
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
        return isset($this->birthday) ? $this->birthday : null;
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
     * Get user's time zone
     * 
     * @return null
     */
    public function getTimezone()
    {
        return null;
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
        return isset($this->website) ? preg_replace('/^([^\r\n]++).*$/s', '$1', $this->website) : null;
    }
    
    /**
     * Get user's address
     * 
     * @return string
     */
    public function getLocation()
    {
        return isset($this->location) ? $this->location : null;
    }
    
    
    /**
     * Get user's bio
     * 
     * @return string
     */
    public function getDescription()
    {
        return isset($this->bio) ? $this->bio : null;
    }
    
    /**
     * Get user's employment
     * 
     * @return Employment
     */
    public function getEmployment()
    {
        return $this->_employment;
    }
    
    /**
     * Get person's employment company.
     * 
     * @return Company
     */
    public function getCompany()
    {
        return isset($this->work[0]->employer) ? $this->work[0]->employer : null;
    }
}
