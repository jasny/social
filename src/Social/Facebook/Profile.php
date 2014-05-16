<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Facebook;

/**
 * Common methods for entities that have a profile on Facebook
 * 
 * @package Facebook
 */
trait Profile
{
    /**
     * Get username on Facebook.
     * Returns the ID is the username is unknown.
     * 
     * @return string
     */
    public function getUsername()
    {
        return isset($this->username) ? $this->username : @$this->id;
    }
    
    /**
     * Get URL to profile on Facebook
     * 
     * @return string
     */
    public function getLink()
    {
        if (isset($this->link)) return $this->link;
        
        if (!isset($this->id) && !isset($this->username)) return null;
        return 'http://www.facebook.com/' . $this->getUsername();
    }
    
    /**
     * Get url to profile picture.
     * 
     * If size is omited the largest available image is returned.
     * $size is an just an indication, the image may not have those exact dimensions.
     * 
     * @param string $size   'square', 'small', 'medium', 'large' or {width}x{height} (eg '800x600' or 'x400')
     * @return string
     */
    public function getPicture($size=null)
    {
        if (!isset($this->id) && !isset($this->username)) return null;
        
        if (!isset($size)) $size = '8192x';
        if (strpos($size, 'x') !== false) {
            list($width, $height) = explode('x');
            $query = "width=$width&height=$height";
        } else {
            $query = "type=$size";
        }
        
        return "http://graph.facebook.com/" . $this->getUsername() . "/picture?$query";
    }
}
