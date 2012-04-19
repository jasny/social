<?php
/**
 * Facebook Graph API connection.
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Facebook;

use Social\Connection as Base;
use Social\Exception;
use Social\Collection;

/**
 * Facebook Graph API connection.
 * @see http://developers.facebook.com/docs/reference/api/
 * 
 * Before you start register your application at https://developers.facebook.com/apps and retrieve an App ID and App Secret
 */
class Connection extends Base
{
    /**
     * Facebook authentication URL
     */
    const authURL = "https://www.facebook.com/dialog/oauth";

    /**
     * Facebook Open Graph API URL
     */
    const graphURL = "https://graph.facebook.com/";
    
    
    /**
     * Application ID
     * @var string
     */
    protected $appId;

    /**
     * Application secret
     * @var string
     */
    protected $appSecret;


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
     * Current user
     * @var Entity
     */
    protected $me;
    

    /**
     * Class constructor.
     * 
     * Passing $user_id is not required to act as the user, you're only required to specify the access token.
     * 
     * @param string        $appId          Application ID
     * @param string        $secret         Application secret
     * @param string|object $access         User's access token or { 'token': string, 'expires': unixtime, [ 'user': facebook id ] }
     * @param int           $accessExpires  Timestamp for when token will expire (supply if $access is a string)
     * @param int|Entity    $user           User's Facebook ID or user entity (optional)
     */
    public function __construct($appId, $appSecret, $access=null, $accessExpires=null, $user=null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;

        if (is_array($access)) $access = (object)$access;
        if (is_object($access)) {
            $this->accessToken = $access->token;
            if (isset($access->expires)) $this->accessExpires = $access->expires;
            if (isset($access->user)) $user = $access->user;
        } else {
            $this->accessToken = $access;
            $this->accessExpires = $accessExpires;
        }
        
        if (isset($user)) {
            if ($user instanceof Entity) $this->me = $user->reconnectTo($this);
              elseif (is_scalar($user)) $this->me = new Entity($this, 'user', array('id' => $user), true);
              else throw new Exception("Was expecting an ID (int) or Entity for \$user, but got a " . (is_object($user) ? get_class($user) : get_type($user)));
        }
    }
    
    /**
     * Create a new Facebook connection using the specified access token.
     * 
     * @param string|object $access         User's access token or { 'token': string, 'expires': unixtime }
     * @param int           $accessExpires  Timestamp for when token will expire (supply if $access is a string)
     * @param int|Entity    $user           User's Facebook ID or user entity (optional)
     */
    public function asUser($access, $accessExpires=null, $user=null)
    {
        return new static($this->appId, $this->appSecret, $access, $accessExpires, $user);
    }
    
    
    /**
     * Get the application ID.
     * 
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
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
     * Get the access info.
     *
     * @return object  { 'token': string, 'expires': unixtime }
     */
    public function getAccessInfo()
    {
        return isset($this->accessToken) ? (object)array('token' => $this->accessToken, 'expires' => $this->accessExpires) : null;
    }
    
    /**
     * Get Facebook Open Graph API URL
     * 
     * @return string
     */
    protected function getBaseUrl()
    {
        return self::graphURL;
    }
    
    /**
     * Generate a unique value, used as 'state' for oauth.
     * 
     * @return string
     */
    protected function getUniqueState()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['REMOTE_ADDR'];
        return md5($ip . $this->appSecret);
    }
    
    
    /**
     * Get authentication url.
     * 
     * For permssions @see http://developers.facebook.com/docs/authentication/permissions/
     * 
     * @param array  $scope        Permission list
     * @param string $redirectUrl
     * @return string
     */
    public function getAuthUrl($scope=null, $redirectUrl=null)
    {
        $redirectUrl = parent::getCurrentUrl($redirectUrl, array('facebook_auth' => 'auth', 'code' => null, 'state' => null));
        if (!isset($redirectUrl)) throw new Exception("Unable to determine the redirect URL, please specify it.");
        
        return $this->getUrl(self::authURL, array('client_id' => $this->appId, 'redirect_uri' => $redirectUrl, 'scope' => $scope, 'state' => $this->getUniqueState()));
    }
    
    /**
     * Handle an authentication response and sets the access token.
     * If $code and $state are omitted, they are taken from $_GET.
     * 
     * @param string $code    Returned code generated by Facebook.
     * @param string $state   Returned state generated by us; false means don't check state
     * @return object  { 'token': string, 'expires': unixtime }
     */
    public function handleAuthResponse($code=null, $state=null)
    {
        if (!isset($code)) {
            if (!isset($_GET['code'])) throw new Exception("Unable to handle authentication response: Facebook didn't return a code.");
            $code = $_GET['code'];
            if (isset($_GET['state'])) $state = $_GET['state'];
        }
        
        $redirectUrl = parent::getCurrentUrl(null, array('code' => null, 'state' => null));
        
        if ($state !== false && $this->getUniqueState() != $state) {
            throw new Exception('Authentication response not accepted. IP mismatch, possible cross-site request forgery.');
        }
        
        $response = $this->httpRequest('GET', "oauth/access_token", array('client_id' => $this->appId, 'client_secret' => $this->appSecret, 'redirect_uri' => $redirectUrl, 'code' => $code));
        parse_str($response, $data);
        if (reset($data) == '') $data = json_decode($response, true);

        if (!isset($data['access_token'])) throw new Exception("Failed to retrieve an access token from Facebook" . (isset($data['error']['message']) ? ': ' . $data['error']['message'] : ''));

        $this->accessToken = $data['access_token'];
        $this->accessExpires = time() + $data['expires'];
        
        return $this->getAccessInfo();
    }

    /**
     * Request a new access token with an extended lifetime of 60 days from now.
     *
     * @return object  { 'token': string, 'expires': unixtime }
     */
    public function extendAccess()
    {
        if (!isset($this->accessToken)) throw new Exception("Unable to extend access token. Access token isn't set.");
        $response = $this->httpRequest('GET', "oauth/access_token", array('client_id' => $this->appId, 'client_secret' => $this->appSecret, 'grant_type' => 'fb_exchange_token', 'fb_exchange_token' => $this->getAccessToken()));

        parse_str($response, $data);
        if (reset($data) == '') $data = json_decode($response, true);

        if (!isset($data['access_token'])) throw new Exception("Failed to extend the access token from Facebook" . (isset($data['error']['message']) ? ': ' . $data['error']['message'] : ''));

        $this->accessToken = $data['access_token'];
        $this->accessExpires = time() + $data['expires'];
        
        return $this->getAccessInfo();
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


    /**
     * Get the URL of the current script.
     *
     * @param string $page    Relative path to page
     * @param array  $params
     * @return string
     */
    static public function getCurrentUrl($page=null, array $params=array())
    {
        if (!isset($params['facebook_auth'])) $params['facebook_auth'] = null;
        $params['code'] = null;
        $params['state'] = null;

        return parent::getCurrentUrl($page, $params);
    }
    
    
    /**
     * Fetch raw data from Facebook.
     * 
     * @param string $id
     * @param array  $params  Get parameters
     * @return array
     */
    public function getData($id, array $params=array())
    {
        $response = $this->httpRequest('GET', $id, ($this->accessToken ? array('access_token' => $this->accessToken) : array('client_id' => $this->appId)) + $params);
        $data = json_decode($response);
        return $data ?: $response;
    }

    /**
     * Fetch an entity (or other data) from Facebook.
     * 
     * @param string $id
     * @param array  $params
     * @return Entity
     */
    public function get($id, array $params=array())
    {
        $data = $this->getData($id, $params);
        return $this->convertData($data, null, false, $params + $this->extractParams($id));
    }
    
    /**
     * Get current user profile.
     * 
     * @return Entity
     */
    public function me()
    {
        if (isset($this->me)) return $this->me;
        if (!$this->isAuth()) throw new Exception("There is no current user. Please set the access token.");
        
        $data = $this->getData('me');
        $this->me = new Entity($this, 'user', $data);
        return $this->me;
    }
    

    /**
     * Create a new entity.
     * 
     * @param string $type
     * @param array  $data
     * @return Entity
     */
    public function create($type, $data=array())
    {
        return new Entity($this, $type, (object)$data);
    }
    
    /**
     * Create a new collection.
     * 
     * @param string $type      Type of entities in the collection (may be omitted)
     * @param array  $data
     * @return Collection
     */
    public function collection($type, $data=array())
    {
        if (is_array($type)) {
            $data = $type;
            $type = null;
        }
        
        return new Collection($this, $type, $data);
    }
    
    /**
     * Create a stub.
     * 
     * For Facebook you may also do { @example $facebook->stub($id) }}, omitting $type.
     * 
     * @param string       $type  Entity type (may be omitted)
     * @param array|string $data  Data or id (required)
     * @return Entity
     */
    public function stub($type, $data=null)
    {
        if (!isset($data)) {
            $data = $type;
            $type = null;
        }
        
        if (is_scalar($data)) $data = array('id' => $data);
        return new Entity($this, $type, (object)$data, true);
    }
    
    
    /**
     * Convert data to Entity, Collection or DateTime.
     * 
     * @param mixed   $data
     * @param string  $type    Entity type
     * @param boolean $stub    If an Entity, asume it's a stub
     * @param object  $source  { 'url': string, 'params': array }
     * @return Entity|Collection|DateTime|mixed
     */
    public function convertData($data, $type=null, $stub=true, $source=null)
    {
        // Don't convert
        if ($data instanceof Entity || $data instanceof Collection || $data instanceof \DateTime) {
            return $data;
        }
        
        // Scalar
        if (is_scalar($data) || is_null($data)) {
            if (preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d$/', $data)) return new \DateTime($data);
            if (isset($type)) return $this->stub($type, $data);
            return $data;
        }

        // Entity
        if ($data instanceof \stdClass && isset($data->id)) return new Entity($this, $type, $data, $stub);
           
        // Collection
        if ($data instanceof \stdClass && isset($data->data) && is_array($data->data)) {
            if (is_string($source)) $source = $this->extractParams($source);
            $nextPage = isset($data->paging->next) ? $data->paging->next = $this->buildUrl($data->paging->next, (array)$source, false) : null; // Make sure the same parameters are used in the next query
            return new Collection($this, $type, $data->data, $nextPage);
        }
        
        // Array or value object
        if (is_array($data) || $data instanceof \stdClass) {
            foreach ($data as &$value) {
                $value = $this->convertData($value, $type);
            }
            return $data;
        }
        
        // Probably some other kind of object
        return $data;
    }
    
    
    /**
     * Serialization
     * { @internal Don't serialze cached objects }}
     * 
     * @return array
     */
    public function __sleep()
    {
        return array('appId', 'appSecret', 'accessToken', 'accessExpires', 'accessTimestamp');
    }
}
