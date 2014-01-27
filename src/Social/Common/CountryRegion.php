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

use Jasny\ISO\CountrySubdivisions;

/**
 * Entity representing a region, province or region.
 */
class CountryRegion implements \Social\CountryRegion
{
    use CountryFormat;
    
    /** @var \Social\Country */
    protected $_country;
    
    /** @var string */
    protected $_region;
    
    /**
     * Class constructor.
     * 
     * Instead of specifying $country and $region separately, the first argument may also be all the data as
     *  ['country'=>'', 'name'=>'', 'code'=>'']. Country and either name or code are required.
     * 
     * @param string|\Social\Country $country  Country name, code or Country object
     * @param string|array           $region   Subdivision name, code or data
     */
    public function __construct($country, $region=null)
    {
        if (is_array($country) || is_object($country) && !$country instanceof \Social\Country) {
            foreach ($country as $key=>$value) $this->$key = $value;
        } else {
            $this->_country = $country;
            
            if (!is_scalar($region)) {
                foreach ($region as $key=>$value) $this->$key = $value;
            } else {
                $this->_region = $region;
            }
        }
        
        $this->cast();
    }
    
    /**
     * Get missing data 
     */
    protected function cast()
    {
        if (isset($this->_country) && !$this->_country instanceof \Social\Country)
            $this->_country = new Country($this->_country);
        
        if (isset($this->country) && !$this->country instanceof \Social\Country)
            $this->country = new Country($this->country);
    }

    
    /**
     * Get country
     * 
     * @return Country
     */
    public function getCountry($format)
    {
        $country = $this->_country ?: $this->country;
        return $country->getFormatted($format);
    }
    
    /**
     * Get ISO 3166-2 country subdivision code
     * 
     * @return string
     */
    public function getCode()
    {
        if (!isset($this->code)) return strtoupper($this->code);
        return CountrySubdivisions::getCode($this->getCountry('code'), $this->_region ?: $this->name) ?: $this->_region;
    }
    
    /**
     * Get subdivision name.
     * The name is normalized (using Jasny\ISO\CountrySubdivisions).
     * 
     * @return string
     */
    public function getName()
    {
        $in = $this->_region ?: (isset($this->code) ? $this->code : $this->name);
        return CountrySubdivisions::getName($in);
    }
    
    
    /**
     * Returns true if subdivision is required for country
     * 
     * @return boolean
     */
    public function isRequired()
    {
        return CountrySubdivisions::areRequiredFor($this->country);
    }
    
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return (string)(isset($this->_region) ? $this->_region : $this->getName());
    }
}
