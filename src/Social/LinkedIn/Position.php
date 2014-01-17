<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\LinkedIn;

use \Social\Common\DateTime;

/**
 * Entity representing a member's positon at a company
 * 
 * @package LinkedIn
 */
class Position implements \Social\Employment
{
    /**
     * Available profile fields
     * 
     * @var array
     */
    static public $fields = [
        'id',
        'title',
        'summary',
        'start-date',
        'end-date',
        'is-current',
        'company'
    ];
    
    
    /** @var string */
    protected $_headline;
    
    
    /**
     * Class constructor
     * 
     * @param array|object|string $data  Position or persons headline
     */
    public function __construct($data)
    {
        if (!is_scalar($data)) {
            foreach ($data as $key=>$value) $this->$key = $value;
        } else {
            $this->_headline = $data;
            list($this->title, $this->company) = explode(' at ', $this->_headline);
        }

        $this->cast();
    }
    
    /**
     * Cast some data to entities
     */
    protected function cast()
    {
        if (isset($this->company)) $this->company = new Company($this->company);
        
        if (isset($this->startDate)) $this->startDate = new DateTime($this->startDate);
        if (isset($this->endDate)) $this->endDate = new DateTime($this->endDate);
    }
    
    
    /**
     * Get job title
     * 
     * @return string
     */
    public function getJobTitle()
    {
        return isset($this->title) ? $this->title : (isset($this->_title) ? $this->_title : null);
    }
    
    /**
     * Get (long) job description
     * 
     * @return string
     */
    public function getJobDescription()
    {
        return isset($this->summary) ? $this->summary : null;
    }
    
    
    /**
     * Get postal code / zip code
     * 
     * @return Company
     */
    public function getCompany()
    {
        return isset($this->company) ? $this->company : null;
    }
    
    /**
     * Get address of the company.
     * Shortcut of <code>$position->getCompany()->getLocation()</code>
     * 
     * @return Address
     */
    public function getLocation()
    {
        return isset($this->company) ? $this->company->getAddress() : null;
    }
    
    
    /**
     * Get date when first employed
     * 
     * @return DateTime
     */
    public function getStartDate()
    {
        return isset($this->startDate) ? $this->startDate : null;
    }
    
    /**
     * Get date when last employed
     * 
     * @return \DateTime
     */
    public function getEndDate()
    {
        return isset($this->endDate) ? $this->endDate : null;
    }
    
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        if (isset($this->_headline)) return $this->_headline;
        
        if (isset($this->company))
            return (isset($this->title) ? $this->title : 'works') . ' at ' . $this->company;
        
        return isset($this->title) ? $this->title : '';
    }
}
