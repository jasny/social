<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * Common interface for an entity that has a webprofile at the provider.
 */
interface Profile
{
    /**
     * Get the entity at another service provider.
     * 
     * {{ @internal Do *not* search at $service, only return a stub if the id or username is known. }}
     * 
     * <code>
     *   $service->me()->atProvider('twitter');
     * </code>
     * 
     * @param Connection $service  Service provider
     * @return Profile
     */
    public function atProvider($service);
    
    
    /**
     * Get URL to profile
     * 
     * @return string
     */
    public function getLink();
    
    /**
     * Get url to profile picture.
     * 
     * If size is omited the largest available image is returned.
     * $size is an just an indication, the image may not have those exact dimensions.
     * 
     * @param string $size   width x height (eg '800x600' or 'x400')
     * @return string
     */
    public function getPicture($size=null);
    
    /**
     * Get url to profile locale (= language)
     * 
     * @return string
     */
    public function getLocale();

    
    /**
     * Get profile description
     * 
     * @return string
     */
    public function getDescription();
}
