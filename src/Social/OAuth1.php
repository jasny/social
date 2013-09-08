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
 * Trait to be used by a connection to implement OAuth 1.
 */
trait OAuth1
{
    /**
     * Use $_SESSION for authentication
     * @var boolean
     */
    protected $authUseSession = false;

    /**
     * Application's consumer key
     * @var string
     */
    protected $consumerKey;

    /**
     * Application's consumer secret
     * @var string
     */
    protected $consumerSecret;
    
    /**
     * User's access token
     * @var string
     */
    protected $accessToken;

    /**
     * User's access token secret
     * @var string
     */
    protected $accessSecret;

    
    /**
     * Set application credentials.
     * 
     * @param string          $consumerKey     Application's consumer key
     * @param string          $consumerSecret  Application's consumer secret
     */
    protected function setCredentials($consumerKey, $consumerSecret)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
    }
    
    /**
     * Get the application's consumer key.
     * 
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }
    
    /**
     * Get user's access token.
     * 
     * @return string 
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
    
    /**
     * Get user's access token secret.
     * 
     * @return string 
     */
    public function getAccessSecret()
    {
        return $this->accessSecret;
    }
    
    /**
     * Get the user's access info.
     *
     * @return object  { 'token': token, 'secret': secret }
     */
    protected function getAccessInfo()
    {
        if (!isset($this->accessToken)) return null;
        
        $access = (object)array('token' => $this->accessToken, 'secret' => $this->accessSecret);
        return $access;
    }
    
    /**
     * Set the access info.
     * 
     * @param array|object $access [ access token, expire timestamp, facebook id ] or { 'token': string, 'expires': unixtime, 'user': facebook id }
     */
    protected function setAccessInfo($access)
    {
        if (!isset($access)) return;
        
        if (isset($_SESSION) && $access === $_SESSION) {
            $this->authUseSession = true;
            $access = @$_SESSION[$this->authParam];
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
     * Create a new connection using the specified access token.
     *
     * Passing a user is not required to act as the user, you're only required to specify the access token and secret.
     *
     * @param array|object $access [ access token, expire timestamp, facebook id ] or { 'token': string, 'expires': unixtime, 'user': facebook id }
     */
    public function asUser($access)
    {
        return new static($this->consumerKey, $this->consumerSecret, $access);
    }
    
    
    /**
     * Generate a unique oAuth nonce.
     * 
     * @return string
     */
    protected function getNonce()
    {
        return md5(uniqid());
    }

    /**
     * Generate oAuth signature.
     * 
     * @param string $method  Request type: GET, POST or DELETE 
     * @param string $url
     * @param array  $params  Request paramaters + oAuth parameters
     */
    protected function getOAuthSignature($method, $url, array $params)
    {
        // Extract additional paramaters from the URL
        if (strpos($url, '?') !== false) {
            list($url, $query) = explode('?', $url, 2);
            $query_params = null;
            parse_str($query, $query_params);
            $params += $query_params;
        }
        
        $url = $this->processPlaceholders($url, $params);

        // Sign
        $user_secret = isset($params['oauth_token_secret']) ? $params['oauth_token_secret'] : $this->accessSecret;
        unset($params['oauth_token_secret']);

        ksort($params);
        
        $query = static::buildHttpQuery($params);
        $base_string = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($query);
        $signing_key = rawurlencode($this->consumerSecret) . '&' . rawurlencode($user_secret);

        return base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
    }
    
    /**
     * Get Authorization header.
     * 
     * @param string $method  GET, POST or DELETE
     * @param string $url
     * @param array  $params  Request parameters
     * @param array  $oauth   Additional/Alternative oAuth values
     * @return string
     */
    protected function getAuthorizationHeader($method, $url, $params, array $oauth=[])
    {
        $oauth += array(
          'oauth_consumer_key' => $this->consumerKey,
          'oauth_nonce' => $this->getNonce(),
          'oauth_signature_method' => "HMAC-SHA1",
          'oauth_timestamp' => time(),
          'oauth_version' => "1.0"
        );
        
        if (isset($this->accessToken) && !isset($oauth['oauth_token'])) $oauth['oauth_token'] = $this->accessToken;
        $oauth['oauth_signature'] = $this->getOAuthSignature($method, $url, $params + $oauth);
        
        unset($oauth['oauth_token_secret']);
        ksort($oauth);
        
        $parts = [];
        foreach ($oauth as $key=>$value) {
            $parts[] = $key . '="' . rawurlencode($value) . '"';
        }

        return 'OAuth ' . join(', ', $parts);
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

        $multipart = $request->method == 'POST' && isset($request->headers['Content-Type'])
            && $request->headers['Content-Type'] == 'multipart/form-data';
        if ($multipart) $request->url = preg_replace('/\?.*$/', '', $request->url);

        $oauth = isset($request->headers['oauth']) ? $request->headers['oauth'] : [];
        unset($request->headers['oauth']);

        $request->headers['Authorization'] = $this->getAuthorizationHeader(
            $request->method,
            $this->getUrl($url),
            !$multipart ? $request->params : [],
            $oauth
        );
        
        return $request;
    }

    
    /**
     * Get the URL of the current script.
     *
     * @param string $page    Relative path to page
     * @param array  $params
     * @return string
     */
    public static function getCurrentUrl($page=null, array $params=[])
    {
        if (!isset($params[static::AUTH_PARAM])) $params[static::AUTH_PARAM] = null;
        $params['oauth_token'] = null;
        $params['oauth_verifier'] = null;

        return parent::getCurrentUrl($page, $params);
    }

    /**
     * Get authentication url.
     * Temporary accesss information is automatically stored to a session.
     *
     * @param int    $level      'authorize', 'authenticate'
     * @param string $returnUrl  The URL to return to after successfully authenticating.
     * @param object $access     Will be filled with the temporary access information.
     * @return string
     */
    protected function getAuthUrl($level='authenticate', $returnUrl=null, &$tmpAccess=null)
    {
        if (!isset($returnUrl)) {
            $returnUrl = $this->getCurrentUrl($returnUrl, array(static::AUTH_PARAM => 'auth'));
            if (!isset($returnUrl)) throw new Exception("Unable to determine the redirect URL, please specify it.");
        }

        $response = $this->post('oauth/request_token', ['oauth'=>['oauth_callback' => $returnUrl]]);
        parse_str($response, $tmpAccess);
        
        $_SESSION[static::AUTH_PARAM . ':tmp_access'] = $tmpAccess;
        
        return $this->getUrl('oauth/' . $level, array('oauth_token' => $tmpAccess['oauth_token']));
    }

    /**
     * Handle an authentication response and sets the access token.
     * If $oauthVerifier is omitted, it is taken from $_GET.
     * If $tmpAccess is omitted, it is taken from the session.
     * 
     * @param string $oauthVerifier  Returned oauth_verifier.
     * @param object $tmpAccess      Temp access information.
     */
    public function handleAuthResponse($oauthVerifier=null, $tmpAccess=null)
    {
        if (!isset($oauthVerifier)) {
            if (!isset($_GET['oauth_verifier'])) throw new Exception("Unable to handle authentication response: oauth_verifier wasn't returned by Twitter.");
            $oauthVerifier = $_GET['oauth_verifier'];
        }
        
        $sessionkey = static::AUTH_PARAM . ':tmp_access';
        if (!isset($tmpAccess) && isset($_SESSION[$sessionkey])) $tmpAccess = $_SESSION[$sessionkey];
        if (!isset($tmpAccess['oauth_token'])) throw new Exception("Unable to handle authentication response: the temporary access token is unknown.");
        unset($tmpAccess['oauth_callback_confirmed']);

        $response = $this->get('oauth/access_token', [], ['oauth'=>['oauth_verifier' => $oauthVerifier] + $tmpAccess]);
        parse_str($response, $data);

        $this->accessToken = $data['oauth_token'];
        $this->accessSecret = $data['oauth_token_secret'];
        
        if ($this->authUseSession) $_SESSION[static::AUTH_PARAM] = $this->getAccessInfo();

        return $this->getAccessInfo();
    }
    
    /**
     * Authenticate
     */
    public function auth($level='authenticate')
    {
        if ($this->isAuth()) return;
        
        if (!empty($_GET[self::AUTH_PARAM]) && $_GET[self::AUTH_PARAM] == 'auth') {
            $this->handleAuthResponse();
            return self::redirect($this->getCurrentUrl());
        }
  
        self::redirect($this->getAuthUrl($level));
    }

    /**
     * Check if a user is authenticated.
     * 
     * @return boolean
     */
    public function isAuth()
    {
        return isset($this->accessToken);
    }
}
