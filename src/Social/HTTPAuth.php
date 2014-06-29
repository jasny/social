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
 * Trait to be used by a connection to implement OAuth1.
 */
trait HTTPAuth
{
    /**
     * Use $_SESSION for authentication
     * @var boolean
     */
    protected $authUseSession = false;

    /**
     * Get the username.
     * 
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * Get the password.
     * 
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * Get the user's access info.
     *
     * @return object  { 'username': string, 'password': string }
     */
    public function getAccessInfo()
    {
        if (!isset($this->curl_opts[CURLOPT_USERNAME])) return null;
        
        return (object)[
            'username'=>$this->curl_opts[CURLOPT_USERNAME],
            'password'=>$this->curl_opts[CURLOPT_PASSWORD]
        ];
    }
    
    /**
     * Set the access info.
     * 
     * @param array|object $access  [ token, secret ] or { 'token': string, 'secret': string }
     */
    protected function setAccessInfo($access)
    {
        if (!isset($access)) return;
        
        if (isset($_SESSION) && $access === $_SESSION) {
            $this->authUseSession = true;
            $access = @$_SESSION[static::serviceProvider . ':access'];
        }
        
        if (is_array($access) && is_int(key($access))) {
            list($username, $password) = $access + array(null, null);
        } elseif (isset($access)) {
            $access = (object)$access;
            $username = $access->username;
            $password = $access->password;
        }

        $this->curl_opts[CURLOPT_USERNAME] = $username;
        $this->curl_opts[CURLOPT_PASSWORD] = $password;
    }
    
    
    /**
     * Create a new connection using the specified username and password.
     *
     * @param array|object $access  [ username, password ] or { 'username': string, 'password': string }
     */
    public function asUser($access)
    {
        return new static($access);
    }
    
    /**
     * Authenticate
     * 
     * @return Connection $this
     */
    public function auth()
    {
        return $this;
    }

    /**
     * Check if a user is authenticated.
     * 
     * @return boolean
     */
    public function isAuth()
    {
        return isset($this->curl_opts[CURLOPT_USERNAME]);
    }
}
