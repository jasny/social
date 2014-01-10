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

use \Social\Common\Employment;

/**
 * Entity representing a user
 * 
 * @package Facebook
 */
class User implements \Social\Person, \Social\User, \Social\Profile
{
    use Profile;
    
    /** @var Employment */
    protected $_employment;

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
        
        if (isset($this->work->location) && !$this->work->location instanceof Location)
            $this->work->location = new Location($this->work->location);
        
        if (isset($this->work) && !empty($this->work)) {
            $this->_employment = new Employment(['job_title'=>$this->work[0]->description,
                 'address'=>$this->work[0]->location, 'company'=>$this->getCompany()]);

            foreach ($this->work as $work) {
                if (isset($work->employer) && !$work->employer instanceof Company)
                    $work->employer = new Company((array)$work->employer + ['location'=>$work->location]);
            }
        }
    }
    
    
    /**
     * Get the user at another service provider.
     * 
     * @param \Social\Connection $service  Service provider
     * @return null
     */
    public function atProvider($service)
    {
        return null;
    }
    
    
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
     * @return Location
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
    
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getFullName() ?: (string)$this->getUsername();
    }
}
