<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Common;

/**
 * Data representing the employment of a person at a company.
 */
class Employment implements \Social\Employment
{
    /**
     * Class constructor
     * 
     * @param array|object $data  Address properties
     */
    public function __construct($data)
    {
        foreach ($data as $key=>$value) {
            $this->$key = $value;
        }
    }
    
    
    /**
     * Get job title
     * 
     * @return string
     */
    public function getJobTitle()
    {
        return isset($this->job_title) ? $this->job_title : null;
    }
    
    /**
     * Get (long) job description
     * 
     * @return string
     */
    public function getJobDescription()
    {
        return isset($this->job_description) ? $this->job_description : null;
    }
    
    
    /**
     * Get postal code / zip code
     * 
     * @return \Social\Company
     */
    public function getCompany()
    {
        return isset($this->company) ? $this->company : null;
    }
    
    /**
     * Get address of the company.
     * Shortcut of <code>$employment->getCompany()->getLocation()</code>
     * 
     * @return \Social\Location
     */
    public function getLocation()
    {
        if (isset($this->address)) return $this->address;
        return isset($this->company) ? $this->company->getLocation() : null;
    }
    
    
    /**
     * Get date when first employed
     * 
     * @return \DateTime
     */
    public function getStartDate()
    {
        return isset($this->start_date) ? $this->start_date : null;
    }
    
    /**
     * Get date when last employed
     * 
     * @return \DateTime
     */
    public function getEndDate()
    {
        return isset($this->end_date) ? $this->end_date : null;
    }
}
