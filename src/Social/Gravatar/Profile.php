<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Gravatar;

use \Social\Common\Location;

/**
 * Profile entity on Gravatar
 * 
 * @package Gravatar
 */
class Profile implements \Social\Person, \Social\Profile, \Social\User
{
    /**
     * Class constructor
     * 
     * @param object|array $data
     */
    public function __construct($data)
    {
        foreach ($data as $key=>$value) {
            $this->$key = $value;
        }
    }
    

    /**
     * Get profile identifier
     * 
     * @return string
     */
    public function getId()
    {
        return $this->hash;
    }
    
    /**
     * Get username on Gravatar.
     * Returns the hash is the username is unknown.
     * 
     * @return string
     */
    public function getUsername()
    {
        return isset($this->preferredUsername) ? $this->preferredUsername : $this->hash;
    }
    
    /**
     * Get URL to profile on Facebook
     * 
     * @return string
     */
    public function getLink()
    {
        return $this->profileUrl;
    }
    
    /**
     * Get url to profile picture.
     * 
     * @see Connection::avatar()
     *
     * @param int|string $size     80 or '80x80'; defaults to 800x800
     * @param string     $default  URL or option
     * @param string     $rating   'g', 'pg', 'r', 'x'
     * @return string
     */
    public function getPicture($size=null, $default='404', $rating=null)
    {
        if (isset($size) && !is_numeric($size)) {
            list($width, $height) = explode('x', $size) + [1=>null];
            $size = max((int)$width, (int)$height);
        }
        if (!isset($size)) $size = 800;
        
        return $this->thumbnailUrl . "?size=$size" . ($default ? "&d=$default" : '') . ($rating ? "&r=$rating" : '');
    }

    
    /**
     * Get the entity at another service provider.
     * 
     * <code>
     *   $twitter_user = $gravatar->user($email)->atProvider('twitter');
     * </code>
     * 
     * @param \Social\Connection $service   Service provider
     * @param boolean            $verified  Must be verified
     * @return \Social\Profile
     */
    public function atProvider($service, $verified=false)
    {
        $domain = null;
        
        if ($service instanceof \Social\Facebook\Connection) $domain = 'facebook.com';
        if ($service instanceof \Social\LinkedIn\Connection) $domain = 'linkedin.com';
        if ($service instanceof \Social\Twitter\Connection) $domain = 'twitter.com';
        
        if ($service instanceof \Social\Google\Base) $domain = 'profiles.google.com';
        if ($service instanceof \Social\YouTube\Connection) $domain = 'youtube.com';
        
        if (!isset($domain)) return null;
        
        foreach ($this->accounts as $account) {
            if ($account->domain != $domain) continue;
            
            if ($verified && !$account->verified) return null;
            return $service->user($account->username);
        }
        
        return null;
    }

    
    /**
     * Get person's full name
     * 
     * @return string
     */
    public function getName()
    {
        return isset($this->displayName) ? $this->displayName : null;
    }
    
    /**
     * Get person's first name
     * 
     * @return string
     */
    public function getFirstName()
    {
        return isset($this->name->givenName) ? $this->name->givenName : $this->getName();
    }
    
    /**
     * Get person's last name
     * 
     * @return string
     */
    public function getLastName()
    {
        return isset($this->name->familyName) ? $this->name->familyName : null;
    }
    
    
    /**
     * Get person's gender
     * 
     * @return null
     */
    public function getGender()
    {
        return null;
    }
    
    /**
     * Get person's date of birth
     * 
     * @return null
     */
    public function getDateOfBirth()
    {
        return null;
    }
    
    
    /**
     * Get url to person's locale (= language)
     * 
     * @return null
     */
    public function getLocale()
    {
        return null;
    }
    
    /**
     * Get person's location
     * 
     * @return Location
     */
    public function getLocation()
    {
        if (!isset($this->currentLocation)) return null;
        
        $values = explode(', ', $this->currentLocation);
        if (count($values) == 2) $values = array_combine(['city', 'country'], $values);
        if (count($values) == 3) $values = array_combine(['city', 'state', 'country'], $values);
        
        return new Location($values);
    }

    /**
     * Get person's timezone
     * 
     * @return null
     */
    public function getTimezone()
    {
        return null;
    }
    
    /**
     * Get person's email address
     * 
     * @return string
     */
    public function getEmail()
    {
        foreach ($this->emails as $email) {
            if ($email->primary) return $email->value;
        }
        return null;
    }
    
    /**
     * Get person's website.
     * Return the main (or first) website, if multiple are known.
     * 
     * @return string
     */
    public function getWebsite()
    {
        return isset($this->urls[0]) ? $this->urls[0]->value : null;
    }
    
    
    /**
     * Get information about person's employment
     * 
     * @return null
     */
    public function getEmployment()
    {
        return null;
    }
    
    /**
     * Get person's employment company.
     * 
     * @return null
     */
    public function getCompany()
    {
        return null;
    }
    
    /**
     * Get person's bio
     * 
     * @return string
     */
    public function getDescription()
    {
        return isset($this->aboutMe) ? $this->aboutMe : null;
    }
}
