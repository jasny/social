<?php
/**
 * Twitter API connection.
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\OAuth1;
use Social\Exception;

/**
 * Twitter API connection.
 * @see https://dev.twitter.com/docs
 * 
 * Before you start, register your application at https://dev.twitter.com/apps and retrieve a custumor key and consumer secret.
 */
class Connection extends OAuth1
{
    /**
     * Twitter API URL
     */
    const baseURL = "https://api.twitter.com/";

    
    /**
     * Current user
     * @var Entity
     */
    protected $me;
    
    /**
     * Get Twitter API URL.
     * 
     * @return string
     */
    protected function getBaseUrl()
    {
        return self::baseURL;
    }    
}