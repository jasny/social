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

use Jasny\ISO\Countries;

/**
 * An entity representing a country.
 */
class Country implements \Social\Country
{
    use CountryFormat;
    
    /** @var string */
    protected $_in;
    
    
    /**
     * Class constructor.
     * Either the country name or code need to be supplied
     * 
     * @param string|array $country  Country name, code or ['code'=>'', 'name'=>'']
     */
    public function __construct($country)
    {
        if (!is_scalar($country)) {
            foreach ($country as $key=>$value) $this->$key = $value;
        } else {
            $this->_in = $country;
        }
    }
    
    
    /**
     * Get ISO 3166-1 country code
     * 
     * @return string
     */
    public function getCode()
    {
        if (!isset($this->code)) return strtoupper($this->code);
        if (strlen($this->_in) == 2) return strtoupper($this->_in);
        return Countries::getCode($this->_in ?: $this->name);
    }
    
    /**
     * Get country name.
     * The name is normalized (using Jasny\ISO\Countries).
     * 
     * @return string
     */
    public function getName()
    {
        $in = $this->_in ?: (isset($this->code) ? $this->code : $this->name);
        return Countries::getName($in) ?: $in;
    }
    
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->_in ?: (string)$this->getName();
    }
}
