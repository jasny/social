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
 * Add getFormatted() method to Country of CountrySubdivision
 */
trait CountryFormat
{
    /**
     * Get country name, code or object.
     * 
     * @param string $format  null (returns object), 'code' or 'name'
     * @return \Social\Country|string
     */
    public function getFormatted($format)
    {
        switch ($format) {
            case 'code': return $this->getCode();
            case 'name': return $this->getName();
            default:     return $this;
        }
    }
}