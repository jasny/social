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
 * Trait to be used by a connection to implement OAuth 2.
 */
trait OAuth2
{
    /**
     * Use $_SESSION for authentication
     * @var boolean
     */
    protected $authUseSession = false;

    /**
     * Application's client ID
     * @var string
     */
    protected $clientId;

    /**
     * Application secret
     * @var string
     */
    protected $clientSecret;

    /**
     * User's access token
     * @var string
     */
    protected $accessToken;

    /**
     * Timestamp for when access token will expire
     * @var int
     */
    protected $accessExpires;

    
    /**
     * Set the application's client credentials
     * 
     * @param string $clientId
     * @param string $clientSecret
     */
    protected function setCredentials($clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }
    
    /**
     * Get the application client ID.
     * 
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }
    
    /**
     * Get the user's access token.
     * 
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
    
    /**
     * Get the timestamp of when the access token will expire.
     * 
     * @return int
     */
    public function getAccessExpires()
    {
        return $this->accessExpires;
    }
    
    /**
     * Set the access info.
     * 
     * @param array|object $access [ user's access token, expire timestamp, facebook id ] or { 'token': string, 'expires': unixtime, 'user': facebook id }
     */
    protected function setAccessInfo($access)
    {
        if (!isset($access)) return;
        
        if (isset($_SESSION) && $access === $_SESSION) {
            $this->authUseSession = true;
            $access = @$_SESSION[static::AUTH_PARAM];
        }
        
        if (is_array($access) && is_int(key($access))) {
            list($this->accessToken, $this->accessExpires, $user) = $access + array(null, null, null);
        } elseif (isset($access)) {
            $access = (object)$access;
            $this->accessToken = $access->token;
            if (isset($access->expires)) $this->accessExpires = $access->expires;
            if (isset($access->user)) $user = $access->user;
        }
        
        if (isset($user)) {
            if ($user instanceof Entity) {
                $this->me = $user->reconnectTo($this);
            } elseif (is_scalar($user)) {
                $this->me = $this->entity('user', array('id' => $user), Entity::AUTOEXPAND);
            } else {
                $type = (is_object($user) ? get_class($user) : get_type($user));
                throw new \Exception("Was expecting an ID (int) or Entity for user, but got a $type");
            }
        }
    }
    
    /**
     * Get the access info.
     *
     * @return object  { 'token': string, 'expires': unixtime }
     */
    public function getAccessInfo()
    {
        if (!isset($this->accessToken)) return null;
        return (object)['token' => $this->accessToken, 'expires' => $this->accessExpires];
    }
    
    /**
     * Generate a unique value, used as 'state' for oauth.
     * 
     * @return string
     */
    protected function getUniqueState()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['REMOTE_ADDR'];
        return md5($ip . $this->clientSecret);
    }
    
    /**
     * Create a new connection using the specified access token.
     * 
     * @param array|object $access [ user's access token, expire timestamp, facebook id ] or { 'token': string, 'expires': unixtime, 'user': facebook id }
     */
    public function asUser($access)
    {
        return new static($this->clientId, $this->clientSecret, $access);
    }
    
    
   /**
     * Initialise an HTTP request object.
     *
     * @param object|string  $request  url or { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': mixed }
     * @return object
     */
    protected function initRequest($request)
    {
        $request = parent::initRequest($request);
        
        if ($this->accessToken) $request->params['oauth_token'] = $this->accessToken;
         else $request->params['client_id'] = $this->clientId;
        
        return $request;
    }
    
    
     /**
     * Get the URL of the current script.
     *
     * @param string $page    Relative path to page
     * @param array  $params
     * @return string
     */
    static public function getCurrentUrl($page=null, array $params=[])
    {
        if (!isset($params[static::AUTH_PARAM])) $params[static::AUTH_PARAM] = null;
        $params['code'] = null;
        $params['state'] = null;

        return parent::getCurrentUrl($page, $params);
    }
    
    /**
     * Get authentication url.
     * 
     * For permissions @see http://developers.facebook.com/docs/authentication/permissions/
     * 
     * @param array  $scope        Permission list
     * @param string $redirectUrl  Redirect to this URL after authentication
     * @param array  $params       Additional URL parameters
     * @return string
     */
    public function getAuthUrl($scope='*', $redirectUrl=null, $params=[])
    {
        $redirectUrl = $this->getCurrentUrl($redirectUrl, ['facebook_auth'=>'auth']);
        if (!isset($redirectUrl)) throw new Exception("Unable to determine the redirect URL, please specify it.");
        
        return $this->getUrl(static::authURL, ['client_id' => $this->clientId, 'redirect_uri' => $redirectUrl,
            'scope' => $scope, 'state' => $this->getUniqueState()] + $params);
    }
    
    /**
     * Handle an authentication response and sets the access token.
     * If $code and $state are omitted, they are taken from $_GET.
     * 
     * @param string $code   Returned code generated by Facebook.
     * @param string $state  Returned state generated by us; false means don't check state
     * @return object  { 'token': string, 'expires': unixtime }
     */
    public function handleAuthResponse($code=null, $state=null)
    {
        if (!isset($code)) {
            if (!isset($_GET['code'])) {
                throw new Exception("Unable to handle authentication response: API didn't return a code.");
            }
            
            $code = $_GET['code'];
            if (isset($_GET['state'])) $state = $_GET['state'];
        }
        
        $redirectUrl = $this->getCurrentUrl();
        
        if ($state !== false && $this->getUniqueState() != $state) {
            throw new \Exception('Authentication response not accepted. IP mismatch, possible cross-site request'
                . 'forgery.');
        }
        
        $response = $this->get('oauth/access_token', ['client_id'=>$this->clientId,
            'client_secret'=>$this->clientSecret, 'redirect_uri'=>$redirectUrl, 'code'=>$code]);
        if (is_string($response)) parse_str($response, $data);

        if (!isset($data['access_token'])) {
            $error = @$data['error']['message'] ?: (is_scalar($response) ? $response : json_encode($response));
            throw new \Exception("Failed to retrieve an access token: $error");
        }
        
        $this->accessToken = $data['access_token'];
        $this->accessExpires = time() + $data['expires'];
        
        if ($this->authUseSession) $_SESSION[static::AUTH_PARAM] = $this->getAccessInfo();
        return $this->getAccessInfo();
    }

    /**
     * Get authentication url.
     * 
     * @param array  $scope        Permission list
     * @param string $redirectUrl  Redirect to this URL after authentication
     * @param array  $params       Additional URL parameters
     */
    public function auth($scope='*', $redirectUrl=null, $params=[])
    {
        if ($this->isAuth()) return;
        
        if (!empty($_GET[self::AUTH_PARAM]) && $_GET[self::AUTH_PARAM] == 'auth') {
            $this->handleAuthResponse();
            return self::redirect($this->getCurrentUrl());
        }
  
        self::redirect($this->getAuthUrl($scope, $redirectUrl, $params));
    }

   /**
     * Check if the access token is expired (or will expire soon).
     *
     * @param int $margin  Number of seconds the session has to be alive.
     * @return boolean
     */
    public function isExpired($margin=5)
    {
        return isset($this->accessExpires) && $this->accessExpires < (time() + $margin);
    }
    
    /**
     * Check if a user is authenticated.
     * 
     * @return boolean
     */
    public function isAuth()
    {
        return isset($this->accessToken) && !$this->isExpired();
    }
}
