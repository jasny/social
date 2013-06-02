<?php
/**
 * Twitter API connection.
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Connection as Base;
use Social\OAuth1;
use Social\Entity;
use Social\Exception;

/**
 * Twitter API connection.
 * @see https://dev.twitter.com/docs
 * 
 * Before you start, register your application at https://dev.twitter.com/apps and retrieve a custumor key and consumer secret.
 */
class Connection extends Base
{
    use OAuth1;

    /**
     * Paramater used as key for GET and SESSION
     */
    const AUTH_PARAM = 'twitter_auth';
    
    /**
     * Twitter REST API URL
     */
    const restURL = "https://api.twitter.com/1.1/";

    /**
     * Twitter upload API URL
     */
    const uploadURL = "https://upload.twitter.com/1.1/";
    
    /**
     * Twitter OAuth API URL
     */
    const oauthURL = "https://api.twitter.com/";
    
    /**
     * Twitter streaming API URL
     */
    const streamUrl = "https://stream.twitter.com/1.1/";

    /**
     * Twitter streaming API URL for user stream
     */
    const userstreamUrl = "https://userstream.twitter.com/1.1/";

    /**
     * Twitter streaming API URL for site stream
     */
    const sitestreamUrl = "https://sitestream.twitter.com/1.1/";
    
    
    /**
     * Entity type per resource
     * @var array
     */
    private static $resourceTypes = array(
        'statuses'                  => 'tweet',
        'statuses/*/retweeted_by'   => 'user',
        'statuses/oembed'           => null,
        'direct_messages'           => 'direct_message',
        'followers'                 => 'user',
        'friends'                   => 'user',
        'friendships'               => '@user',
        'friendships/exists'        => null,
        'users'                     => 'user',
        'users/suggestions'         => null,
        'users/profile_image'       => null,
        'favorites'                 => 'tweet',
        'lists'                     => 'list',
        'lists/statuses'            => 'tweet',
        'lists/members'             => 'user',
        'account'                   => 'me',
        'account/rate_limit_status' => null,
        'account/totals'            => null,
        'account/settings'          => null,
        'notifications'             => 'user',
        'saved_searches'            => 'saved_search',
        'geo'                       => 'place',
        'geo/reverse_geocode'       => null,
        'blocks'                    => 'user',
        'report_spam'               => 'user',
    );

    /**
     * API url per resource
     * @var array
     */
    public static $resourceApi = array(
        '*'                          => self::restURL,
        'oauth'                      => self::oauthURL,
        'statuses/update_with_media' => self::uploadURL,
        'statuses/filter'            => self::streamUrl,
        'statuses/sample'            => self::streamUrl,
        'statuses/firehose'          => self::streamUrl,
        'user'                       => self::userstreamUrl,
        'site'                       => self::sitestreamUrl,
    );
    
    /**
     * Resource that require a multipart POST
     * @var array
     */
    public static $resourcesMultipart = array(
        'account/update_profile_background_image' => true,
        'account/update_profile_image'            => true,
        'statuses/update_with_media'              => true,
    );
    
    /**
     * Default paramaters per resource.
     * @var array
     */
    private static $defaultParams = array(
        'statuses/home_timeline'     => array('max_id' => null),
        'statuses/mentions'          => array('max_id' => null),
        'statuses/retweeted_by_me'   => array('max_id' => null),
        'statuses/retweeted_to_me'   => array('max_id' => null),
        'statuses/retweets_of_me'    => array('max_id' => null),
        'statuses/user_timeline'     => array('max_id' => null, 'trim_user' => true),
        'statuses/retweeted_to_user' => array('max_id' => null),
        'statuses/retweeted_by_user' => array('max_id' => null),
        'follower/ids'               => array('stringify_ids' => 1),
        'friends/ids'                => array('stringify_ids' => 1),
    );
    
    
    /**
     * Current user
     * @var Entity
     */
    protected $me;
    
    
    /**
     * Class constructor.
     * 
     * Passing $user is not required to act as the user, you're only required to specify the access token and secret.
     * 
     * @param string          $consumerKey     Application's consumer key
     * @param string          $consumerSecret  Application's consumer secret
     * @param string|object   $access          User's access token or { 'token': string, 'secret': string, [ 'user': twitter id ] }
     * @param string          $accessSecret    User's access token secret (supply if $access is a string)
     * @param int|string|Me   $user            User's Twitter ID/username or Twitter\Me entity (optional)
     */
    public function __construct($consumerKey, $consumerSecret, $access=null, $accessSecret=null, $user=null)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        
        if (isset($_SESSION) && $access === $_SESSION) {
            $this->authUseSession = true;
            $access = @$_SESSION[$this->authParam];
        }
        
        if (is_array($access)) $access = (object)$access;
        if (is_object($access)) {
            $this->accessToken = $access->token;
            $this->accessSecret = $access->secret;
            if (isset($access->me) && !isset($user)) $user = $access->me;
        } else {
            $this->accessToken = $access;
            $this->accessSecret = $accessSecret;
        }

        if (isset($user)) {
            if ($user instanceof Me) $this->me = $user->reconnectTo($this);
              elseif (is_scalar($user)) $this->me = new Me($this, $user, true);
              else trigger_error("Was expecting an ID (int), username (string) or Twitter\\Me entity for \$user, but got a " . (is_object($user) ? get_class($user) : get_type($user)), E_USER_WARNING);
        }
    }

    /**
     * Create a new Facebook connection using the specified access token.
     *
     * Passing $user is not required to act as the user, you're only required to specify the access token and secret.
     *
     * @param string|object   $access        User's access token or { 'token': string, 'secret': string, [ 'user_id': twitter id ] }
     * @param int             $accessSecret  User's access token secret (supply if $access is a string)
     * @param int|string|Me   $user          User's Twitter ID/username or Twitter\Me entity (optional)
     */
    public function asUser($access, $accessSecret=null, $user=null)
    {
        return new static($this->consumerKey, $this->consumerSecret, $access, $accessSecret, $user);
    }
    
    /**
     * Get the user's access info.
     *
     * @return object  { 'token': token, 'secret': secret }
     */
    public function getAccessInto()
    {
        return $this->getOAuth1AccessInfo();
    }

    
    /**
     * Get Twitter API URL based on de resource.
     * 
     * @param string $resource
     * @return string
     */
    protected function getBaseUrl($resource=null)
    {
        $resource = self::normalizeResource($resource);
        
        if ($resource) do {
            if (isset(self::$resourceApi[$resource])) return self::$resourceApi[$resource];
            $resource = dirname($resource);
        } while ($resource != '.');

        return self::$resourceApi['*'];
    }
    
    /**
     * Get authentication url.
     * Temporary accesss information is automatically stored to a session.
     *
     * @see https://dev.twitter.com/docs/api/1/get/oauth/authenticate
     *
     * @param int    $level        'authorize' or 'authenticate'
     * @param string $callbackUrl  The URL to return to after successfully authenticating.
     * @param object $tmpAccess    Will be filled with the temporary access information.
     * @return string
     */
    public function getAuthUrl($level='authenticate', $callbackUrl=null, &$tmpAccess=null)
    {
        $callbackUrl = $this->getCurrentUrl($callbackUrl, array(self::AUTH_PARAM => 'auth'));
        return $this->getOAuth1Url($level, $callbackUrl, $tmpAccess);
    }
    
    /**
     * Handle an authentication response and sets the access token.
     * If $oauthVerifier is omitted, it is taken from $_GET.
     * If $tmpAccess is omitted, it is taken from the session.
     * 
     * @param string $oauthVerifier  Returned oauth_verifier generated by Twitter.
     * @param object $tmpAccess      Temp access information.
     */
    public function handleAuthResponse($oauthVerifier=null, $tmpAccess=null)
    {
        return $this->handleOAuth1Response($oauthVerifier, $tmpAccess);
    }
    
    /**
     * Authenticate using twitter
     */
    public function auth($level='authenticate')
    {
        if ($this->isAuth()) return;
        
        if (!empty($_GET[self::AUTH_PARAM]) && $_GET[self::AUTH_PARAM] == 'auth') {
            $this->handleAuthResponse();
            return self::redirect($this->getCurrentUrl());
        }
  
        return self::redirect($this->getAuthUrl($level));
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
     * Get the URL of the current script.
     *
     * @param string $page    Relative path to page
     * @param array  $params
     * @return string
     */
    public static function getCurrentUrl($page=null, array $params=array())
    {
        if (!isset($params['twitter_auth'])) $params['twitter_auth'] = null;
        $params['oauth_token'] = null;
        $params['oauth_verifier'] = null;

        return parent::getCurrentUrl($page, $params);
    }
    
    
    /**
     * Get normalized resource from URL
     * 
     * @param string $resource
     * @return string
     */
    public static function normalizeResource($resource)
    {
        return preg_replace(array('~/(?:\d+|:\w+)(?=/|$)~', '~(\.\w+(\?.*)?|\?.*)$~'), array('/*', ''), $resource); // Replace id's by '*' and remove file extension
    }
    
    /**
     * Get default parameters for resource.
     * 
     * @param string $resource
     * @return array
     */
    public static function getDefaultParams($resource)
    {
        $resource = self::normalizeResource($resource);
        return isset(self::$defaultParams[$resource]) ? self::$defaultParams[$resource] : array();
    }

    /**
     * Get entity type for resource.
     * 
     * @param string $resource 
     * @return string
     */
    public static function detectType($resource)
    {
        $resource = self::normalizeResource($resource);
        
        do {
            if (array_key_exists($resource, self::$resourceTypes)) return self::$resourceTypes[$resource];
            $resource = dirname($resource);
        } while ($resource && $resource != '.');
        
        return null;
    }
    
    /**
     * Check if resource requires a multipart POST.
     * 
     * @param string $resource
     * @return boolean 
     */
    protected static function detectMultipart($resource)
    {
        $resource = self::normalizeResource($resource);
        return !empty(self::$resourcesMultipart[$resource]);
    }
    
    /**
     * Initialise an HTTP request object.
     *
     * @param object|string  $request  url or { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': mixed }
     * @return object
     */
    protected function initRequest($request)
    {
        if (is_scalar($request)) $request = (object)array('url' => $request);
          elseif (is_array($request)) $request = (object)$request;
        
        if (!isset($request->url)) {
            if (isset($request->resource)) $request->url = $request->resource;
              else throw new Exception("Invalid request, no URL specified");
        }

        if (!isset($request->method)) $request->method = 'GET';
        
        list($url, $params) = explode('?', $request->url, 2) + array(1 => null);
        if (pathinfo($url, PATHINFO_EXTENSION) == '') $request->url = "$url.json" . ($params ? "?$params" : '');
        $request->url = $this->processPlaceholders($request->url, $request->params); // Need to process placeholders now for OAuth
        
        $request->params = (isset($request->params) ? $request->params : array()) + self::getDefaultParams($request->url);
        
        if (!isset($request->headers)) $request->headers = array();
        if (self::detectMultipart($request->url)) $request->headers['Content-Type'] = 'multipart/form-data';
        
        if (!isset($request->convert)) $request->convert = true;

        $multipart = $request->method == 'POST' && isset($request->headers['Content-Type']) && $request->headers['Content-Type'] == 'multipart/form-data';
        if ($multipart) $request->url = preg_replace('/\?.*$/', '', $request->url);

        $oauth = isset($request->headers['oauth']) ? $request->headers['oauth'] : array();
        unset($request->headers['oauth']);
        $request->headers['Authorization'] = $this->getOAuth1Header($request->method, $this->getUrl($url), !$multipart ? $request->params : array(), $oauth);
        
        return $request;
    }

    
    /**
     * Run a single prepared HTTP request.
     * 
     * @param object|array  $request  Value object or array of objects { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': mixed, 'writefunction': callback  }
     * @return string
     */
    protected function request($request)
    {
        return is_array($request) ? $this->singleRequest($request) : $this->multiRequest($request);
    }
    
    /**
     * Run a single prepared HTTP request.
     * 
     * @param object|string  $request  url or { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': mixed, 'writefunction': callback  }
     * @return string
     */
    protected function singleRequest($request)
    {
        $request = $this->initRequest($request);
        $response = $this->request($request);
        $data = json_decode($response);
        
        if (!isset($data)) return $response;

        // Follow the cursor to load all data
        if (is_object($data) && !isset($request->params['cursor']) && !empty($data->next_cursor_str)) {
            list($key) = array_diff(array_keys(get_object_vars($data)), array('next_cursor', 'previous_cursor', 'next_cursor_str', 'previous_cursor_str'));
            
            while ($data->next_cursor_str) {
                $request->params['cursor'] = $data->next_cursor_str;
                $response = $this->httpRequest($request->method, $request->url, $request->params, $request->headers);
                $newdata = json_decode($response);
                
                if (!empty($newdata->$key)) $data->$key = array_merge($data->$key, $newdata->$key);
                $data->next_cursor = $newdata->next_cursor;
                $data->next_cursor_str = $newdata->next_cursor_str;
            }
        }
        
        return $this->convertResponse($request, $data);
    }
    
    /**
     * Run multiple HTTP requests in parallel.
     * 
     * @param array $requests  Array of value objects { 'method': string, 'url': string, 'params': array, 'headers': array, convert: mixed }
     * @return array
     */
    protected function multiRequest(array $requests)
    {
        foreach ($requests as &$request) {
            $request = $this->initRequest($request);
        }
        
        $results = $this->httpMultiRequest($requests);
        $lastResults = array();

        foreach ($results as $i=>&$response) {
            $data = json_decode($response);
            if (isset($data)) $response = $data;
        }
        
        // Follow the cursor to load all data
        do {
            $next = array();
            foreach ($results as $i=>&$data) {
                if (is_object($data) && (isset($lastResults[$i]) || !isset($requests[$i]->params['cursor'])) && !empty($data->next_cursor_str)) {
                    $next[$i] = $requests[$i];
                    $next[$i]->params['cursor'] = $data->next_cursor_str;
                }
            }
            
            if (!$next) break;
            
            $lastResults = $this->httpMultiRequest($next);

            foreach ($lastResults as $i=>$result) {
                $data =& $results[$i];
                $newdata = json_decode($result);
                
                // Something went wrong, let's not go into an endless loop
                if (!is_object($newdata)) {
                    $results[$i] = null;
                    continue;
                }
                
                list($key) = array_diff(array_keys(get_object_vars($data)), array('next_cursor', 'previous_cursor', 'next_cursor_str', 'previous_cursor_str'));
                
                if (!empty($newdata->$key)) $data->$key = array_merge($data->$key, $newdata->$key);
                $data->next_cursor = $newdata->next_cursor;
                $data->next_cursor_str = $newdata->next_cursor_str;
            }
        } while (true); // breaks above
        
        foreach ($results as $i=>&$data) {
            $data = $this->convertResponse($request[$i], $data);
        }
        
        return $results;
    }

    /**
     * Convert returned data of request
     * 
     * @param object $request
     * @param mixed $data
     * @return mixed
     */
    protected function convertResponse($request, $data)
    {
        if (!$request->convert) return $data;
        
        $type = $this->detectType($request->url);
        $data = $this->convertData($data, $type, false, $request);

        $convert = $request->convert;
        if ($convert instanceof \Social\Data) {
            $data = $convert->setData($data);
        } elseif (is_callable($convert)) {
            $data = $convert($data, $i);
        }
        
        return $data;
    }

    
    /**
     * Stream content from Twitter.
     * 
     * @param callback $writefunction  Stream content to this function
     * @param string   $resource
     * @param array    $params         Request parameters
     * @return boolean
     */
    public function stream($writefunction, $resource, array $params=array())
    {
        $method = $resource == 'statuses/filter' ? 'POST' : 'GET';
        $params += $this->getDefaultParams($resource);
        
        $response = $this->httpRequest($method, $resource . (pathinfo($resource, PATHINFO_EXTENSION) ? "" : ".json"), $params, array(), array(), $writefunction);
        return $response;
    }

    
    /**
     * Returns tweets that match a specified query.
     * 
     * https://dev.twitter.com/docs/api/1/get/search
     * 
     * @param type $query
     * @param array $params 
     * @param boolean $convert  Convert to collection, false returns raw data
     * @return Collection  of Tweets
     */
    public function search($query, array $params=array(), $convert=true)
    {
        $params['q'] = $query;
        return $this->get('search/tweets', $params, $convert);
    }

    /**
     * Search for Twitter users.
     * 
     * https://dev.twitter.com/docs/api/1/get/users/search
     * 
     * @param type $query
     * @param array $params 
     * @param boolean $convert  Convert to collection, false returns raw data
     * @return Collection  of Users
     */
    public function searchUsers($query, array $params=array(), $convert=true)
    {
        $params['q'] = $query;
        return $this->get('users/search', $params, $convert);
    }
    
    
    /**
     * Get current user profile.
     * 
     * @return Me
     */
    public function me()
    {
        if (!isset($this->me)) {
            if (!$this->isAuth()) throw new Exception("There is no current user. Please set the access token.");
            $this->me = new Me($this, null, Entity::AUTOEXPAND);
        }
        
        return $this->me;
    }

    /**
     * Factory method for an entity.
     * 
     * @param string    $type  'me', 'user', 'tweet', 'direct_message', 'user_list', 'saved_search' or 'place'
     * @param array|int $data  Properties or ID
     * @param int       $stub  Entity::NO_STUB, Entity::STUB or Entity::AUTOEXPAND
     * @return Entity
     */
    public function entity($type, $data=array(), $stub=Entity::AUTOEXPAND)
    {
        if (isset($type) && $type[0] == '@') {
            $type = substr($type, 1);
            $stub = true;
        }
        
        if ($type != 'me' && $type != 'user' && $type != 'tweet' && $type != 'direct_message' && $type != 'user_list' && $type != 'saved_search' && $type != 'place') {
            throw new Exception("Unable to create a Twitter entity: unknown entity type '$type'");
        }
        
        $class = strtolower(preg_replace('/\W/', '', $type));
        $class = __NAMESPACE__ . '\\' . join('', array_map('ucfirst', explode('_', $class)));

        return new $class($this, $data, $stub);
    }
    
    /**
     * Factory method a collection.
     * 
     * @param string $type  Type of entities in the collection (may be omitted)
     * @param array  $data
     * @param int    $stub  Entity::NO_STUB, Entity::STUB or Entity::AUTOEXPAND
     * @return Collection
     */
    public function collection($type, array $data=array(), $stub=Entity::STUB)
    {
        if ($type != 'me' && $type != 'user' && $type != 'tweet' && $type != 'direct_message' && $type != 'user_list' && $type != 'saved_search' && $type != 'place') {
            throw new Exception("Unable to create a Twitter collection: unknown entity type '$type'");
        }
        
        return $this->convertData($data, $type, $stub);
    }

    /**
     * Short notation for $twitter->entity('user', $data, $stub);
     * 
     * @param array|int|string $data  Properties or Twitter ID/username
     * @param int              $stub  Entity::NO_STUB, Entity::STUB or Entity::AUTOEXPAND
     * @return User
     */
    public function user($data, $stub=Entity::AUTOEXPAND)
    {
        return $this->entity('user', $data, $stub);
    }
    
    
    /**
     * Convert data to Entity, Collection or DateTime.
     * 
     * @param mixed    $data
     * @param string   $type     Entity type
     * @param boolean  $stub     If an Entity, asume it's a stub
     * @param object   $request  Request used to get this data
     * @return Entity|Collection|DateTime|mixed
     */
    public function convertData($data, $type=null, $stub=Entity::NO_STUB, $request=null)
    {
        // Don't convert
        if ($data instanceof Entity || $data instanceof Collection || $data instanceof \DateTime) {
            return $data;
        }
        
        // Scalar
        if (is_scalar($data) || is_null($data)) {
            if (preg_match('/^\w{3}\s\w{3}\s\d+\s\d+:\d+:\d+\s\+\d{4}\s\d{4}$/', $data)) return new \DateTime($data);
            if (isset($type)) return $this->entity($type, $data, ENTITY::STUB);
            return $data;
        }

        if (isset($type)) {
            // Entity
            if ($data instanceof \stdClass && isset($data->id)) {
                return $this->entity($type, $data, $stub);
            }

            // Collection
            if ($data instanceof \stdClass && array_key_exists('next_cursor', $data)) {
                list($key) = array_diff(array_keys(get_object_vars($data)), array('next_cursor', 'previous_cursor', 'next_cursor_str', 'previous_cursor_str'));
                $request->params['cursor'] = $data->next_cursor_str;

                foreach ($data->$key as &$value) $value = $this->entity($type, $value, $stub);
                return new Collection($this, $data->$key, $data->next_cursor_str ? $request : null);
            }

            if (is_array($data) && $type) {
                if ($request && array_key_exists('max_id', $request->params)) {
                    $last = end($data);
                    $request->params['max_id'] = $last && isset($last->id) ? self::decrementId(isset($last->id_str) ? $last->id_str : $last->id) : null;
                }

                foreach ($data as &$value) $value = $this->entity($type, $value, $stub);
                return new Collection($this, $data, $request && !empty($request->params['max_id']) ? $request : null);
            }
        }
        
        // Value object
        if ($data instanceof \stdClass) {
            foreach ($data as $key=>&$value) {
                $type = $key == 'user' || $key == 'user_mentions' ? 'user' : ($key == 'status' ? 'tweet' : null);
                $value = $this->convertData($value, $type);
            }
            return $data;
        }
        
        // Array
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = $this->convertData($value);
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
        if ($this->me) {
            if (property_exists($this->me, 'user_id')) $this->_me = $this->me->user_id;
              elseif (property_exists($this->me, 'screen_name')) $this->_me = $this->me->screen_name;
        }
        
        return array('appId', 'appSecret', 'accessToken', 'accessExpires', 'accessTimestamp', '_me');
    }
    
    /**
     * Unserialization
     * 
     * @return array
     */
    public function __wakeup()
    {
        if (isset($this->_me)) $this->me = new Me($this, $this->_me);
        unset($this->_me);
    }
    
    
    /**
     * Subtract 1 from ID.
     * 
     * @param string $id  A big integer
     * @return string
     */
    static private function decrementId($id)
    {
        // We have bcsub :)
        if (function_exists('bcsub')) return bcsub($id, 1);
        
        // No bcsub :/
        $i = strlen($id) - 1;
        while ($id[$i] == 0) $id[$i++] = 9;
        $id[$i] = $id[i] - 1;
        
        return $id;
    }
}