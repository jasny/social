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
 */
trait OAuth1
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
    protected function getOAuth1AccessInfo()
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
     * @param string $method  Request type: GET, POST or DELETE 
     * @param string $url
     * @param array  $params  Request paramaters + oAuth parameters
     */
    protected function getOAuth1Signature($method, $url, array $params)
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
        
        $base_string = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode(self::buildHttpQuery($params));
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
    protected function getOAuth1Header($method, $url, $params, array $oauth=array())
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
        
        $parts = array();
        foreach ($oauth as $key=>$value) {
            $parts[] = $key . '="' . rawurlencode($value) . '"';
        }

        return 'OAuth ' . join(', ', $parts);
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
    protected function getOAuth1Url($level='authenticate', $returnUrl=null, &$tmpAccess=null)
    {
        if (!isset($returnUrl)) {
            $returnUrl = $this->getCurrentUrl($returnUrl, array(static::AUTH_PARAM => 'auth'));
            if (!isset($returnUrl)) throw new Exception("Unable to determine the redirect URL, please specify it.");
        }

        $response = $this->httpRequest('POST', 'oauth/request_token', array('oauth'=>array('oauth_callback' => $returnUrl)));
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
    public function handleOAuth1Response($oauthVerifier=null, $tmpAccess=null)
    {
        if (!isset($oauthVerifier)) {
            if (!isset($_GET['oauth_verifier'])) throw new Exception("Unable to handle authentication response: oauth_verifier wasn't returned by Twitter.");
            $oauthVerifier = $_GET['oauth_verifier'];
        }
        
        $sessionkey = static::AUTH_PARAM . ':tmp_access';
        if (!isset($tmpAccess) && isset($_SESSION[$sessionkey])) $tmpAccess = $_SESSION[$sessionkey];
        if (!isset($tmpAccess['oauth_token'])) throw new Exception("Unable to handle authentication response: the temporary access token is unknown.");
        unset($tmpAccess['oauth_callback_confirmed']);

        $response = $this->httpRequest('GET', "oauth/access_token", array(), array('oauth'=>array('oauth_verifier' => $oauthVerifier) + $tmpAccess));
        parse_str($response, $data);

        $this->accessToken = $data['oauth_token'];
        $this->accessSecret = $data['oauth_token_secret'];
        
        if ($this->authUseSession) $_SESSION[static::AUTH_PARAM] = $this->getAccessInfo();

        return $this->getAccessInfo();
    }
}
