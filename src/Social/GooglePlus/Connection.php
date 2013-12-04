<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\GooglePlus;

use Social\Google\Base as Google;

/**
 * Google+ API connection
 * @see https://developers.google.com/+/api/
 * @package GooglePlus
 * 
 * Before you start register your application at https://code.google.com/apis/console/#access and retrieve a client ID
 *  and secret.
 * 
 * OAuth2 scopes:
 *   - plus.login
 *   - plus.me
 */
class Connection extends Google
{
    /**
     * Api name
     * @var string 
     */
    protected $apiName = "plus";
    
    /**
     * Api version
     * @var string 
     */
    protected $apiVersion = "v1";
}
