<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Facebook;

/**
 * Entity representing a company
 */
class Company implements \Social\Company, \Social\Profile
{
    use Profile;
    
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
        
        $this->cast();
    }
    
    /**
     * Cast part of the data to entities
     */
    protected function cast()
    {
        if (isset($this->location) && !$this->location instanceof Location)
            $this->location = new Location($this->location);
    }
    
    
    /**
     * Get the company at another service provider.
     * 
     * @param Connection $service  Service provider
     * @return Profile
     */
    public function atProvider($service)
    {
        return null;
    }
    
    
    /**
     * Get company's name
     * 
     * @return string
     */
    public function getName()
    {
        return isset($this->name) ? $this->name : null;
    }
    
    /**
     * Get company's general email address
     * 
     * @return string
     */
    public function getEmail()
    {
        return null;
    }
    
    /**
     * Get company's website.
     * Return the main (or first) website, if multiple are known.
     * 
     * @return string
     */
    public function getWebsite()
    {
        return isset($this->website) ? preg_replace('/\r?\n.*$/', '', $this->website) : null;
    }
    
    /**
     * Get company's location
     * 
     * @return Address
     */
    public function getLocation()
    {
        return isset($this->location) ? $this->location : null;
    }
    
    /**
     * Get company's locale
     * 
     * @return null
     */
    public function getLocale()
    {
        return null;
    }
    
    
    /**
     * Get description of the company
     * 
     * @return string
     */
    public function getDescription()
    {
        return null;
    }
    
    
    /**
     * Return company's name
     * 
     * @return string
     */
    public function __toString()
    {
        return isset($this->name) ? $this->name : '';
    }
}
