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
     * @param string $consumerKey     Application's consumer key
     * @param string $consumerSecret  Application's consumer secret
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
     * @return object  { 'token': string, 'secret': string }
     */
    public function getAccessInfo()
    {
        if (!isset($this->accessToken)) return null;
        
        $access = (object)['token'=>$this->accessToken, 'secret'=>$this->accessSecret];
        return $access;
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
            list($this->accessToken, $this->accessExpires, $user) = $access + array(null, null, null);
        } elseif (isset($access)) {
            $access = (object)$access;
            if (isset($access->oauth_token)) {
                $this->accessToken = $access->oauth_token;
                $this->accessSecret = $access->oauth_token_secret;
            } else {
                $this->accessToken = $access->token;
                $this->accessSecret = $access->secret;
                if (isset($access->user)) $user = $access->user;
            }
        }
    }
    
    /**
     * Create a new connection using the specified access token.
     *
     * Passing a user id is not required, you're only required to specify the access token and secret.
     *
     * @param array|object $access          [ token, secret ] or { 'token': string, 'secret': string }
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
     * @param object|string  $request  url or value object
     * @return object
     */
    protected function initRequest($request)
    {
        if (is_array($request)) $request = (object)$request;
        if (preg_match('~^oauth/~', is_object($request) ? $request->url : $request)) $request->no_ext = true;
        
        $request = parent::initRequest($request);

        $multipart = $request->method == 'POST' && isset($request->headers['Content-Type'])
            && $request->headers['Content-Type'] == 'multipart/form-data';

        $oauth = isset($request->oauth) ? $request->oauth : [];

        $request->headers['Authorization'] = $this->getAuthorizationHeader(
            $request->method,
            $this->getFullUrl($request->url),
            (!$multipart ? $request->params : []) + $request->queryParams + $this->queryParams,
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
        $params['oauth_token'] = null;
        $params['oauth_verifier'] = null;

        return parent::getCurrentUrl($page, $params);
    }


    /**
     * Store temporary access information to session.
     * 
     * @param array $access
     */
    protected static function storeTmpAccess($access)
    {
        $_SESSION[static::serviceProvider . ':tmp_access'] = $access;
    }
    
    /**
     * Retrieve temporary access information from session.
     * 
     * @return array
     */
    protected static function retrieveTmpAccess()
    {
        return @$_SESSION[static::serviceProvider . ':tmp_access'];
    }
    
    /**
     * Get authentication url.
     * Temporary accesss information is automatically stored to a session.
     *
     * @param string $returnUrl  The URL to return to after successfully authenticating.
     * @return string
     */
    protected function getAuthUrl($returnUrl=null)
    {
        if (!isset($returnUrl)) {
            $returnUrl = $this->getCurrentUrl($returnUrl);
            if (!isset($returnUrl)) throw new Exception("Unable to determine the redirect URL, please specify it.");
        }

	$oauth = ['oauth_callback'=>$returnUrl];
        $request = $this->initRequest(['method'=>'POST', 'url'=>'oauth/request_token', 'oauth'=>$oauth]);
        $response = $this->request($request);
        parse_str($response, $tmpAccess);
        
        $this->storeTmpAccess($tmpAccess);
        
        return $this->getFullUrl('oauth/authorize', ['oauth_token' => $tmpAccess['oauth_token']]);
    }

    /**
     * Handle an authentication response and sets the access token.

     * If $oauthToken is omitted, it is taken from $_GET.
     * If $oauthVerifier is omitted, it is taken from $_GET.
     * 
     * @param string $oauthToken     Returned oauth_token.
     * @param string $oauthVerifier  Returned oauth_verifier.
     * @param object $tmpAccess      Temp access information.
     */
    public function handleAuthResponse($oauthToken=null, $oauthVerifier=null)
    {
        if (!isset($oauthToken)) {
            if (!isset($_GET['oauth_token']))
                throw new AuthException("Unable to handle authentication response: oauth_token wasn't returned.");
            $oauthToken = $_GET['oauth_token'];
        }

        if (!isset($oauthVerifier)) {
            if (!isset($_GET['oauth_verifier']))
                throw new AuthException("Unable to handle authentication response: oauth_verifier wasn't returned.");
            $oauthVerifier = $_GET['oauth_verifier'];
        }
        
        $oauth = $this->retrieveTmpAccess();
        if (!isset($oauth['oauth_token']))
            throw new AuthException("Unable to handle authentication response: the temporary access token is unknown.");
        if ($oauthToken != $oauth['oauth_token'])
            throw new AuthException("Unable to handle authentication response: the temporary access token doesn't match.");
        
        $oauth += ['oauth_verifier' => $oauthVerifier];
        unset($oauth['oauth_callback_confirmed']);
        
        $request = $this->initRequest(['method'=>'GET', 'url'=>'oauth/access_token', 'oauth'=>$oauth]);

        $response = $this->request($request);
        parse_str($response, $data);
        
        $this->setAccessInfo($data);
        if ($this->authUseSession) $_SESSION[static::serviceProvider . ':access'] = $this->getAccessInfo();

        return $this->getAccessInfo();
    }
    
    /**
     * Authenticate
     * 
     * @return Connection $this
     */
    public function auth()
    {
        if ($this->isAuth()) return $this;
        
        if (isset($_GET['oauth_verifier'])) {
            $this->handleAuthResponse();
            self::redirect($this->getCurrentUrl());
        }
  
        self::redirect($this->getAuthUrl());
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

    /**
     * Get the current user.
     * 
     * @return object
     */
    public function me()
    {
        return $this->get('account/verify_credentials');
    }
}
