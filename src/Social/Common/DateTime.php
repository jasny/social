<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Common;

/**
 * Value representing a date/time
 */
class DateTime extends \DateTime
{
    protected $_in;
    
    /**
     * Class constructor
     * 
     * @param array|object|string $date
     */
    public function __construct($date)
    {
        if (!is_scalar($date)) {
            foreach ($date as $key=>$value) $this->$key = $value;

            parent::__construct();
            parent::setDate(isset($this->year) ? $this->year : 1900, isset($this->month) ? $this->month : 1,
                isset($this->day) ? $this->day : 1);
        } else {
            $this->_in = $in;
            parent::__construct($date);
        }
    }
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->_in ?: $this->format('c');
    }
}
