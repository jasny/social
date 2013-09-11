<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * Interface to indicate the the API support user authentication
 */
interface Auth
{
    /**
     * Authenticate,
     * 
     * @return Connection $this
     */
    public function auth();
    
    /**
     * Check if a user is authenticated.
     * 
     * @return boolean
     */
    public function isAuth();
    
    
    /**
     * Get current user profile.
     * 
     * @return object
     */
    public function me();
    
    
    /**
     * Create a new connection using the specified access token.
     *
     * @param mixed $access
     */
    public function asUser($access);
}