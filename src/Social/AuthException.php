<?php
/**
 * Jasny Social
 * World's best PHP library for Social APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * Authentication exception.
 * 
 * Error code is based on HTTP response status:
 *  - 400: Our application send a bad request
 *  - 403: The user denied the request
 *  - 50*: An error occured with the web service 
 */
class AuthException extends \Exception
{
    /**
     * Class constructor
     * 
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($message, $code=400, $previous=null)
    {
        parent::__construct($message, $code, $previous);
    }
}
