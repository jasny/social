<?php
/**
 * Base class for OAUth1 connection.
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * OAUth1 connection.
 * 
 * @package Social
 */
abstract class OAuth1 extends Connection
{
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
     * Class constructor.
     * 
     * @param string        $consumerKey     Application's consumer key
     * @param string        $consumerSecret  Application's consumer secret
     * @param string|object $access          User's access token or { 'token': string, 'secret': string }
     * @param string        $accessSecret    User's access token secret (supply if $access is a string)
     */
    public function __construct($consumerKey, $consumerSecret, $access=null, $accessSecret=null)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        
        if (is_array($access)) $access = (object)$access;
        if (is_object($access)) {
            $this->accessToken = $access->token;
            $this->accessSecret = $access->secret;
        } else {
            $this->accessToken = $access;
            $this->accessSecret = $accessSecret;
        }
    }
    
    /**
     * Create a new connection using the specified access token.
     * 
     * @param string|object $access        User's access token or { 'token': string, 'secret': string }
     * @param int           $accessSecret  User's access token secret (supply if $access is a string)
     */
    public function asUser($access, $accessSecret=null)
    {
        return new static($this->consumerKey, $this->consumerSecret, $access, $accessSecret);
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
    public function getAccessInfo()
    {
        if (!isset($this->accessToken)) return null;
        
        $access = (object)array('token' => $this->accessToken, 'secret' => $this->accessSecret);
        return $access;
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
     * @param string $type    Request type: GET, POST or DELETE 
     * @param string $url
     * @param array  $params  Request paramaters + oAuth parameters
     */
    public function getOAuthSignature($type, $url, array $params)
    {
        // Extract additional paramaters from the URL
        if (strpos($url, '?') !== false) {
            $query_params = null;
            list($url, $query) = explode('?', $url, 2);
            parse_str($query, $query_params);
            $params += $query_params;
        }

        // Sign
        $user_secret = isset($params['oauth_token_secret']) ? $params['oauth_token_secret'] : $this->accessSecret;
        unset($params['oauth_token_secret']);

        ksort($params);

        $base_string = strtoupper($type) . '&' . rawurlencode($url) . '&' . rawurlencode($this->buildHttpQuery($params));
        $signing_key = rawurlencode($this->consumerSecret) . '&' . rawurlencode($user_secret);

        return base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
    }
    
    /**
     * Get Authentication header.
     * 
     * @param string $type    GET, POST or DELETE
     * @param string $url
     * @param array  $params  POST parameters
     * @param array  $oauth   Additional/Alternative oAuth values
     * @return string
     */
    public function getAuthorizationHeader($type, $url, $params, array $oauth=array())
    {
        $oauth += array(
          'oauth_consumer_key' => $this->consumerKey,
          'oauth_nonce' => $this->getNonce(),
          'oauth_signature_method' => "HMAC-SHA1",
          'oauth_timestamp' => time(),
          'oauth_version' => "1.0"
        );
        
        if (isset($this->accessToken) && !isset($oauth['oauth_token'])) $oauth['oauth_token'] = $this->accessToken;
        $oauth['oauth_signature'] = $this->getOAuthSignature($type, $url, $params + $oauth);
        
        unset($oauth['oauth_token_secret']);
        ksort($oauth);
        
        $parts = array();
        foreach ($oauth as $key=>$value) {
            $parts[] = $key . '="' . rawurlencode($value) . '"';
        }

        return 'OAuth ' . join(', ', $parts);
    }
    
    /**
     * Do an HTTP request.
     * 
     * @param string   $type           GET, POST or DELETE
     * @param string   $url
     * @param array    $params         Request parameters
     * @param array    $headers        Additional HTTP headers
     * @param array    $oauth          Additional oAUth parameters
     * @param callback $writefunction  Stream content to this function, instead of returning it as result
     * @return string
     */
    protected function httpRequest($type, $url, $params=null, array $headers=array(), array $oauth=array(), $writefunction=null)
    {
        $multipart = $type == 'POST' && isset($headers['Content-Type']) && $headers['Content-Type'] == 'multipart/form-data';

        $url = $this->getUrl($url);
        $headers['Authorization'] = $this->getAuthorizationHeader($type, $url, $multipart ? array() : $params, $oauth);

        return parent::httpRequest($type, $url, $params, $headers, $writefunction);
    }
    
    
    /**
     * Get authentication url.
     * Temporary accesss information is automatically stored to a session.
     *
     * @param int    $level        'authorize' = read/write on users behalf, 'authenticate' = login + user info only
     * @param string $callbackUrl  The URL to return to after successfully authenticating.
     * @param object $access       Will be filled with the temporary access information.
     * @return string
     */
    public function getAuthUrl($level='authorize', $callbackUrl=null, &$tmp_access=null)
    {
        if (!isset($callbackUrl)) {
            $callbackUrl = $this->getCurrentUrl($callbackUrl, array('twitter_auth' => 'auth'));
            if (!isset($callbackUrl)) throw new Exception("Unable to determine the redirect URL, please specify it.");
        }

        $response = $this->httpRequest('POST', preg_replace('~/[\d\.]+/~', '/', $this->getBaseUrl()) . "oauth/request_token", array(), array(), array('oauth_callback' => $callbackUrl));
        parse_str($response, $tmp_access);
        
        $_SESSION[str_replace('\\', '/', get_class($this)) . ':tmp_access'] = $tmp_access;
        return $this->getUrl(preg_replace('~/[\d\.]+/~', '/', $this->getBaseUrl()) . "oauth/$level", array('oauth_token' => $tmp_access['oauth_token']));
    }
    
    /**
     * Handle an authentication response and sets the access token.
     * If $oauth_verifier is omitted, it is taken from $_GET.
     * If $tmp_access is omitted, it is taken from the session.
     * 
     * @param string $oauth_verifier  Returned oauth_verifier generated by Twitter.
     * @param object $tmp_access      Temp access information.
     */
    public function handleAuthResponse($oauth_verifier=null, $tmp_access=null)
    {
        if (!isset($oauth_verifier)) {
            if (!isset($_GET['oauth_verifier'])) throw new Exception("Unable to handle authentication response: " . (!empty($_GET['denied']) ? "access was denied" : "oauth_verifier wasn't returned"));
            $oauth_verifier = $_GET['oauth_verifier'];
        }
        
        $sessionkey = str_replace('\\', '/', get_class($this)) . ':tmp_access';
        if (!isset($tmp_access) && isset($_SESSION[$sessionkey])) $tmp_access = $_SESSION[$sessionkey];
        if (!isset($tmp_access['oauth_token'])) throw new Exception("Unable to handle authentication response: the temporary access token is unknown.");
        unset($tmp_access['oauth_callback_confirmed']);
        
        $data = null;
        $response = $this->httpRequest('GET', preg_replace('~/[\d\.]+/~', '/', $this->getBaseUrl()) . "oauth/access_token", array(), array(), array('oauth_verifier' => $oauth_verifier) + $tmp_access);
        parse_str($response, $data);

        $this->accessToken = $data['oauth_token'];
        $this->accessSecret = $data['oauth_token_secret'];

        return $this->getAccessInfo();
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