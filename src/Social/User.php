<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social;

/**
 * Common interface for an entity representing a user.
 */
interface User
{
    /**
     * Get the user at another service provider.
     * 
     * {{ @internal Do *not* search at $service, only return a stub if the id or username is known. }}
     * 
     * <code>
     *   $service->me()->atProvider('twitter');
     * </code>
     * 
     * @param Connection $service  Service provider
     * @return User
     */
    public function atProvider($service);
    
    
    /**
     * Get user's full name
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Get user's username
     * 
     * @return string
     */
    public function getUsername();
    
    /**
     * Get url to user's picture.
     * 
     * If size is omited the largest available image is returned.
     * $size is an just an indication, the image may not have those exact dimensions.
     * 
     * @param string $size   width x height (eg '800x600' or 'x400')
     * @return string
     */
    public function getPicture($size=null);
    
    /**
     * Get url to user's locale (= language)
     * 
     * @return string
     */
    public function getLocale();
    
    
    /**
     * Get user's email address
     * 
     * @return string
     */
    public function getEmail();
    
    /**
     * Get user's website.
     * Return the main (or first) website, if multiple are known.
     * 
     * @return string
     */
    public function getWebsite();
    
    /**
     * Get user's description / bio
     * 
     * @return string
     */
    public function getDescription();
    
    /**
     * Get user's location
     * 
     * @return Location
     */
    public function getLocation();
    
    /**
     * Get user's timezone
     * 
     * @return \DateTimeZone
     */
    public function getTimezone();
}
