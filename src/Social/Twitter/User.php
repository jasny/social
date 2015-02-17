<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Twitter;

/**
 * Entity representing a user
 * 
 * @package Twitter
 */
class User extends \Social\Entity implements \Social\User
{
    /**
     * Get the unique identifier of the entity.
     * 
     * @return mixed
     */
    public function getId()
    {
        return !isset($this->id) && isset($this->screen_name) ? $this->screen_name : $this->id;
    }
    
    /**
     * Get user's full name
     * 
     * @return string
     */
    public function getName()
    {
        return isset($this->name) ? $this->name : null;
    }
    
    /**
     * Get url to user's locale (= language)
     * 
     * @return string
     */
    public function getLocale()
    {
        return isset($this->lang) ? $this->lang : null;
    }
    
    /**
     * Get user's timezone
     * 
     * @return string
     */
    public function getTimezone()
    {
        return isset($this->time_zone) ? $this->time_zone : null;
    }
    
    
    /**
     * Get username on Facebook.
     * 
     * @return string
     */
    public function getUsername()
    {
        return isset($this->screen_name) ? $this->screen_name : null;
    }
    
    /**
     * Get URL to profile
     * 
     * @return string
     */
    public function getLink()
    {
        return isset($this->screen_name) ? "http://www.twitter.com/{$this->screen_name}" : null;
    }
    
    /**
     * Get url to profile picture.
     * 
     * If size is omited the largest available image is returned.
     * $size is an just an indication, the image may not have those exact dimensions.
     * 
     * @todo If a $size is square, return one of the smaller images.
     * 
     * @param string $size   'normal', 'bigger' or 'mini', other values will return the original size.
     * @return string
     */
    public function getPicture($size=null)
    {
        if (!empty($this->default_profile_image)) return null; // Don't return twitter's default image
        
        if (!isset($this->profile_image_url) && !isset($this->profile_image_url_https)) return null;
        
        $url = isset($this->profile_image_url_https) && !empty($_SERVER['HTTPS']) ?
            $this->profile_image_url_https : $this->profile_image_url;
        
        $suffix = $size === 'normal' || $size === 'bigger' || $size === 'mini' ? "_$size" : '';
        return preg_replace('/_normal(\.\w+)/', $suffix . '$1', $url);
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
     * @todo Expand t.co url
     *
     * @return string
     */
    public function getWebsite()
    {
        return isset($this->url) ? $this->url : null;
    }
    
    /**
     * Get user's address
     * 
     * @return null
     */
    public function getLocation()
    {
        return isset($this->location) ? $this->location : null;
    }
    
    
    /**
     * Get user's bio
     * 
     * @return null
     */
    public function getDescription()
    {
        return isset($this->description) ? $this->description : null;
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
     * Get the user's birthday
     * 
     * @return null
     */
    public function getDateOfBirth()
    {
        return null;
    }

    /**
     * Get the user's birthday
     * 
     * @return null
     */
    public function getFirstName()
    {
        return null;
    }

    /**
     * Get the user's birthday
     * 
     * @return null
     */
    public function getGender()
    {
        return null;
    }

    /**
     * Get the user's birthday
     * 
     * @return null
     */
    public function getLastName()
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
