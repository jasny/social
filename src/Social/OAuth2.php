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
     * The requested permissions
     * Note: It's not certain the authenticated user has given these permissions
     *
     * @var array|string
     */
    protected $scope;

    
    /**
     * Authentication errors and their code
     * @var array
     */
    protected $authErrors = [
        'access_denied' => 403,
        'server_error' => 500,
        'temporarily_unavailable' => 503
    ];
    
    
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
     * @param array|object $access  [ token, expires ] or { 'token': string, 'expires': unixtime }
     */
    protected function setAccessInfo($access)
    {
        if (!isset($access)) return;
        
        if (isset($_SESSION) && $access === $_SESSION) {
            $this->authUseSession = true;
            $access = @$_SESSION[static::serviceProvider . ':access'];
        }
        
        if (is_array($access) && is_int(key($access))) {
            list($this->accessToken, $this->accessExpires, $user) = $access + array(null, null, null);
        } elseif (isset($access)) {
            $access = (object)$access;
            $this->accessToken = $access->access_token;
            $this->accessExpires = isset($access->expires) ? $access->expires : null;
            if (isset($access->user)) $user = $access->user;
        }
    }
    
    /**
     * Get the access info.
     *
     * @return object  { 'access_token': string, 'expires': unixtime }
     */
    public function getAccessInfo()
    {
        if (!isset($this->accessToken)) return null;
        return (object)['access_token' => $this->accessToken, 'expires' => $this->accessExpires];
    }
    
    /**
     * Generate a unique value, used as 'state' for oauth.
     * 
     * @return string
     */
    protected function getUniqueState()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['REMOTE_ADDR'];
        return static::serviceProvider . ':' . md5($ip . $this->clientSecret);
    }
    
    /**
     * Create a new connection using the specified access token.
     * 
     * @param array|object $access  [ token, expires ] or { 'token': string, 'expires': unixtime }
     */
    public function asUser($access)
    {
        return new static($this->clientId, $this->clientSecret, $access);
    }
    
    
    /**
     * Initialise an HTTP request object.
     *
     * @param object|string $request  url or value object
     * @return object
     */
    protected function initRequest($request)
    {
        $request = parent::initRequest($request);
        
        if ($this->accessToken) $request->queryParams['oauth_token'] = $this->accessToken;
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
        $params['code'] = null;
        $params['state'] = null;

        return parent::getCurrentUrl($page, $params);
    }
    
    /**
     * Get authentication url.
     * 
     * @param array  $scope        Permission list
     * @param string $redirectUrl  Redirect to this URL after authentication
     * @param array  $params       Additional URL parameters
     * @return string
     */
    public function getAuthUrl($scope=null, $redirectUrl=null, $params=[])
    {
        if (!isset($this->clientId))
            throw new \Exception("This application's client ID (required to use OAuth2) isn't set.");

        $redirectUrl = $this->getCurrentUrl($redirectUrl);
        if (!isset($redirectUrl)) throw new Exception("Unable to determine the redirect URL, please specify it.");

        $this->setScope($scope);
        $params = ['client_id'=>$this->clientId, 'redirect_uri'=>$redirectUrl, 'scope'=>join(',', $this->scope),
            'state'=>$this->getUniqueState()] + $params + ['response_type'=>'code'];
        
        return static::buildUrl(static::authURL, $params);
    }

    /**
     * Fetch the OAuth2 access token.
     * 
     * @param array  $params  Parameters
     * @return object
     */
    protected function fetchAccessToken(array $params)
    {
        return $this->post('oauth2/token', $params);
    }
    
    /**
     * Set the authorization scope.
     * 
     * @param array|string $scope
     */
    protected function setScope($scope)
    {
        $this->scope = $scope;
    }
    
    
    /**
     * Handle an authentication response and sets the access token.
     * If $code and $state are omitted, they are taken from $_GET.
     * 
     * @param string $code   Returned code generated by API.
     * @param string $state  Returned state generated by us; false means don't check state
     * @return Connection $this
     */
    public function handleAuthResponse($code=null, $state=null)
    {
        if (!isset($code) && isset($_GET['code'])) {
            $code = $_GET['code'];
            if (isset($_GET['state'])) $state = $_GET['state'];
        }
        
        if (!isset($code)) {
            if (!isset($_GET['error'])) throw new \Exception("Invalid authentication response.");

            $error = $_GET['error'];
            $code = isset($this->authErrors[$error]) ? $this->authErrors[$error] : 400;
            
            $message = isset($_GET['error_description']) ? "{$_GET['error_description']} ($error)" : $error;
            if (isset($_GET['error_uri'])) $error .= " see {$_GET['error_uri']}";
            
            throw new AuthException($message, $code);
        }
        
        $redirectUrl = $this->getCurrentUrl();
        
        if ($state !== false && $this->getUniqueState() != $state) {
            throw new AuthException('Authentication response not accepted. IP mismatch, possible cross-site request'
                . 'forgery.');
        }

        $data = $this->fetchAccessToken(['client_id'=>$this->clientId, 'client_secret'=>$this->clientSecret,
            'redirect_uri'=>$redirectUrl, 'grant_type'=>'authorization_code', 'code'=>$code]);

        if ($data->error) {
            $error = isset($data->error_description) ? "{$data->error_description} ({$data->error})" : $data->error;
            if (isset($data->error_uri)) $error .= " see {$data->error_uri}";
            throw new AuthException($error);
        }

	$expires_in = isset($data->expires) ? $data->expires : (isset($data->expires_in) ? $data->expires_in : null);

        $this->accessToken = $data->access_token;
        $this->accessExpires = isset($expires_in) ? time() + $expires_in : null;
        
        if ($this->authUseSession) $_SESSION[static::serviceProvider . ':access'] = $this->getAccessInfo();
        
        return $this;
    }

    /**
     * Get authentication url.
     * 
     * @param array  $scope        Permission list
     * @param string $redirectUrl  Redirect to this URL after authentication
     * @param array  $params       Additional URL parameters
     * @return Connection $this
     */
    public function auth($scope=null, $redirectUrl=null, $params=[])
    {
        $this->setScope($scope);

        if ($this->isAuth()) return $this;
        
        if (!empty($_GET['state']) && $_GET['state'] == $this->getUniqueState()) {
            $this->handleAuthResponse();
            self::redirect($this->getCurrentUrl());
        } else {
            if (isset($this->accessToken)) $params['grant_type'] = 'refresh_token';
            self::redirect($this->getAuthUrl($scope, $redirectUrl, $params));
        }
    }

    /**
     * Check if the authenticated user has given the requested permissions.
     * If the user doesn't have these permissions, redirect him back to the auth dialog.
     *
     * @return Connection $this
     */
    public function checkScope()
    {
        return $this;
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
