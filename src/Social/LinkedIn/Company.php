<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\LinkedIn;

/**
 * Entity representing a company.
 * 
 * @package LinkedIn
 */
class Company implements \Social\Company, \Social\Profile
{
    /**
     * HQ address
     * @var Address
     */
    protected $_address;
    
    
    /**
     * Available company fields
     * 
     * @var array
     */
    static public $fields = [
        'id',
        'name',
        'universal-name',
        'email-domains',
        'company-type',
        'ticker',
        'website-url',
        'industries',
        'status',
        'logo-url',
        'square-logo-url',
        'blog-rss-url',
        'twitter-id',
        'employee-count-range',
        'specialties',
        'locations',
        'description',
        'stock-exchange',
        'founded-year',
        'end-year',
        'num-followers'
    ];
    
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
        foreach ($this->locations as &$location) {
            $location->address = new Address($location->address);
            if (!isset($this->_address) || !empty($location->isHeadquarters)) $this->_address = $location->address;
        }
    }
    
    
    /**
     * Get the company's ID at another service provider.
     * 
     * <code>
     *   $linkedin->me()->atProvider('twitter');
     * </code>
     * 
     * @param \Social\Connection $service  Service provider
     * @return \Social\Company
     */
    public function atProvider($service)
    {
        if ($service instanceof \Social\Twitter\Connection && isset($this->twitterId))
            return $service->user(['id'=>$this->twitterId]);
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
     * Get url to company's locale (= language)
     * 
     * @return null
     */
    public function getLocale()
    {
        return null;
    }
    
    
    /**
     * Get URL to profile on LinkedIn
     * 
     * @return null
     */
    public function getLink()
    {
        return null;
    }
    
    /**
     * Get url to profile picture.
     * 
     * @param string $size  Not used
     * @return string
     */
    public function getPicture($size=null)
    {
        return isset($this->logoUrl) ? $this->logoUrl : null;
    }
    
    
    /**
     * Get company's email address
     * 
     * @return null
     */
    public function getEmail()
    {
        return null;
    }
    
    /**
     * Get company's website.
     * 
     * @return string
     */
    public function getWebsite()
    {
        return $this->websiteUrl;
    }
    
    /**
     * Get company's address
     * 
     * @return Address
     */
    public function getLocation()
    {
        return $this->_address;
    }
    
    /**
     * Get company's timezone
     * 
     * @return null
     */
    public function getTimezone()
    {
        return null;
    }
    
    /**
     * Get company's description
     * 
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return isset($this->name) ? $this->name : '';
    }
}
