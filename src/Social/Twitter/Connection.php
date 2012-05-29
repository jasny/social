<?php
/**
 * Twitter API connection.
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\OAuth1;
use Social\Entity;
use Social\Exception;

/**
 * Twitter API connection.
 * @see https://dev.twitter.com/docs
 * 
 * Before you start, register your application at https://dev.twitter.com/apps and retrieve a custumor key and consumer secret.
 */
class Connection extends OAuth1
{
    /**
     * Twitter REST API URL
     */
    const restURL = "https://api.twitter.com/1/";

    /**
     * Twitter upload API URL
     */
    const uploadURL = "https://upload.twitter.com/1/";
    
    /**
     * Twitter search API URL
     */
    const searchURL = "https://search.twitter.com/1/";

    /**
     * Twitter OAuth API URL
     */
    const oauthURL = "https://api.twitter.com/";
    
    /**
     * Twitter streaming API URL
     */
    const streamUrl = "https://stream.twitter.com/1/";

    /**
     * Twitter streaming API URL for user stream
     */
    const userstreamUrl = "https://userstream.twitter.com/2/";

    /**
     * Twitter streaming API URL for site stream
     */
    const sitestreamUrl = "https://sitestream.twitter.com/2b/";
    
    
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
        'friendships'               => 'user',
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
        'search'                     => self::searchURL,
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
        'statuses/home_timeline'     => array('count' => 200, 'include_entities' => true),
        'statuses/mentions'          => array('count' => 200, 'include_entities' => true),
        'statuses/retweeted_by_me'   => array('count' => 100, 'include_entities' => true),
        'statuses/retweeted_to_me'   => array('count' => 100, 'include_entities' => true),
        'statuses/retweets_of_me'    => array('count' => 100, 'include_entities' => true),
        'statuses/user_timeline'     => array('count' => 200, 'include_entities' => true),
        'statuses/retweeted_to_user' => array('count' => 100, 'include_entities' => true),
        'statuses/retweeted_by_user' => array('count' => 100, 'include_entities' => true),
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
     * @param int|string|User $user            User's Twitter ID/username or Twitter\Me entity (optional)
     */
    public function __construct($consumerKey, $consumerSecret, $access=null, $accessSecret=null, $user=null)
    {
        parent::__construct($consumerKey, $consumerSecret, $access, $accessSecret);

        if (is_object($access) && isset($access->user)) $user = $access->user;
        
        if (isset($user)) {
            if ($user instanceof User) $this->me = $user->reconnectTo($this);
              elseif (is_scalar($user)) $this->me = new Me($this, $user, true);
              else throw new Exception("Was expecting an ID (int), username (string) or Twitter\\Me entity for \$user, but got a " . (is_object($user) ? get_class($user) : get_type($user)));
        }
    }
    
    /**
     * Create a new Facebook connection using the specified access token.
     *
     * Passing $user is not required to act as the user, you're only required to specify the access token and secret.
     *
     * @param string|object $access        User's access token or { 'token': string, 'secret': string, [ 'user_id': twitter id ] }
     * @param int           $accessSecret  User's access token secret (supply if $access is a string)
     * @param int|string    $user_id       User's Twitter ID/username or Twitter\Me entity (optional)
     */
    public function asUser($access, $accessSecret=null, $user=null)
    {
        return new static($this->appId, $this->appSecret, $access, $accessSecret, $user);
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
     * @param int    $level        'authorize' = read/write on users behalf, 'authenticate' = login + user info only
     * @param string $callbackUrl  The URL to return to after successfully authenticating.
     * @param object $tmpAccess    Will be filled with the temporary access information.
     * @return string
     */
    public function getAuthUrl($level='authorize', $callbackUrl=null, &$tmpAccess=null)
    {
        $callbackUrl = $this->getCurrentUrl($callbackUrl, array('twitter_auth' => $level));
        return parent::getAuthUrl($level, $callbackUrl, $tmpAccess);
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
        return preg_replace(array('~/\d+(?=/|$)~', '~\.\w+$~'), array('/*', ''), $resource); // Replace id's by '*' and normalize
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
            if (isset(self::$resourceTypes[$resource])) return self::$resourceTypes[$resource];
            $resource = dirname($resource);
        } while ($resource != '.');
        
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
     * Do an HTTP request.
     * 
     * @param string $method   GET, POST or DELETE
     * @param string $url
     * @param array  $params   Request parameters
     * @param array  $headers  Additional HTTP headers
     * @param array  $oauth    Additional oAUth parameters
     */
    protected function httpRequest($method, $url, $params=null, array $headers=array(), array $oauth=array())
    {
        $params += $this->getDefaultParams($url);
        if ($this->detectMultipart($url)) $headers['Content-Type'] = 'multipart/form-data';
        
        return parent::httpRequest($method, $url, $params, $headers, $oauth);
    }
    
    /**
     * Fetch from Twitter.
     * 
     * @param string  $resource
     * @param array   $params
     * @param boolean $convert   Convert to entity/collection, false returns raw data
     * @return Entity|Collection|mixed
     */
    public function get($resource, array $params=array(), $convert=true)
    {
        $response = $this->httpRequest('GET', $resource . (pathinfo($resource, PATHINFO_EXTENSION) ? "" : ".json"), $params);
        $data = json_decode($response);
        
        if (!isset($data)) return $response;
        
        if ($convert) {
            $type = $this->detectType($resource);
            $data = $this->convertData($data, $type, false, (object)array('method' => 'GET', 'url' => $resource, 'params' => $params));
        }
        
        return $data;
    }
    
    /**
     * Post to Twitter.
     * 
     * @param string  $resource
     * @param array   $params    POST parameters
     * @param boolean $convert   Convert to entity/collection, false returns raw data
     * @return Entity|Collection|mixed
     */
    public function post($resource, array $params=array(), $convert=true)
    {
        $response = $this->httpRequest('GET', $resource . (pathinfo($resource, PATHINFO_EXTENSION) ? "" : ".json"), $params);
        $data = json_decode($response);
        
        if (!isset($data)) return $response;
        
        if ($convert) {
            $type = $this->detectType($resource);
            $data = $this->convertData($data, $type, false, (object)array('method' => 'POST', 'url' => $resource, 'params' => $params));
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
        $method = $id == 'statuses/filter' ? 'POST' : 'GET';
        
        $response = $this->httpRequest($method, $resource . (pathinfo($resource, PATHINFO_EXTENSION) ? "" : ".json"), $params, array(), array(), $writefunction);
        return $response;
    }
    
    /**
     * Run multiple HTTP requests in parallel.
     * 
     * @param array   $requests  Array of value objects { 'method': string, 'url': string, 'params': array, 'headers': array, 'oauth': array }
     * @param boolean $convert   Convert to entity/collection, false returns raw data
     * @return array
     */
    public function multiRequest(array $requests, $convert=true)
    {
        foreach ($requests as $request) {
            $request->params = (isset($request->params) ? array() : $request->params) + $this->getDefaultParams($request->url);
            if ($this->detectMultipart($request->url)) $request->headers['Content-Type'] = 'multipart/form-data';
        }
        
        $results = parent::multiRequest($requests);
        
        foreach ($results as $i=>&$response) {
            $data = json_decode($response);
            if (!isset($data)) continue;
            
            if ($convert) {
                $type = $this->detectType($requests[$i]->url);
                $data = $this->convertData($data, $type, false, $requests[$i]);
            }
            
            $response = $data;
        }
    }

    
    /**
     * Returns tweets that match a specified query.
     * 
     * https://dev.twitter.com/docs/api/1/get/search
     * 
     * @param type $query
     * @param array $params 
     * @return Collection  of Tweets
     */
    public function search($query, array $params=array())
    {
        $params['q'] = $query;
        return $this->get('search', $params);
    }

    /**
     * Search for Twitter users.
     * 
     * https://dev.twitter.com/docs/api/1/get/users/search
     * 
     * @param type $query
     * @param array $params 
     * @return Collection  of Users
     */
    public function searchUsers($query, array $params=array())
    {
        $params['q'] = $query;
        return $this->get('users/search', $params);
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
            $this->me = new Me($this, null, true);
        }
        
        return $this->me;
    }

    /**
     * Factory method for an entity.
     * 
     * @param string    $type  'me', 'user', 'tweet', 'direct_message', 'user_list', 'saved_search' or 'place'
     * @param array|int $data  Properties or ID
     * @param boolean   $stub  True means that $data doesn't contain all of the entity's properties
     * @return Entity
     */
    public function entity($type, $data=array(), $stub=true)
    {
        if ($type != 'me' && $type != 'user' && $type != 'tweet' && $type != 'direct_message' && $type != 'user_list' && $type != 'saved_search' && $type != 'place') {
            throw new Exception("Unable to create a Twitter entity: unknown entity type '$type'");
        }
        
        $type = preg_replace('/\W|_/', '', $type);
        return new $type($this, $data, $stub);
    }
    
    /**
     * Factory method a collection.
     * 
     * @param string $type  Type of entities in the collection (may be omitted)
     * @param array  $data
     * @return Collection
     */
    public function collection($type=null, $data=array())
    {
        if (is_array($type)) {
            $data = $type;
            $type = null;
        }
        
        return new Collection($this, $type, $data);
    }

    /**
     * Short notation for $twitter->entity('user', $data, $stub);
     * 
     * @param array|int $data  Properties or ID
     * @param boolean   $stub  True means that $data only contains some of the entity's properties
     * @return User
     */
    public function user($data=array(), $stub=true)
    {
        return $twitter->entity('user', $data, $stub);
    }
    
    
    /**
     * Convert data to Entity, Collection or DateTime.
     * 
     * @param mixed    $data
     * @param string   $type     Entity type
     * @param boolean  $stub     If an Entity, asume it's a stub
     * @param object   $request  Request used to get data
     * @return Entity|Collection|DateTime|mixed
     */
    public function convertData($data, $type=null, $stub=false, $request=null)
    {
        // Don't convert
        if ($data instanceof Entity || $data instanceof Collection || $data instanceof \DateTime) {
            return $data;
        }
        
        // Scalar
        if (is_scalar($data) || is_null($data)) {
            if (preg_match('/^\w{3}\s\w{3}\s\d+\s\d+:\d+:\d+\s\+\d{4}\s\d{4}$/', $data)) return new \DateTime($data);
            if (isset($type)) return $this->stub($type, $data);
            return $data;
        }

        // Entity
        if ($data instanceof \stdClass && isset($data->id)) return $this->entity($type, $data, $stub);
        
        // Collection
        if ($data instanceof \stdClass && isset($data->next_cursor)) {
            $nextPage = null; // TODO: calc next page
            return new Collection($this, $type, $data->data, $nextPage);
        }

        if (is_array($data) && is_object(reset($data))) {
            $nextPage = null; // TODO: calc next page
            return new Collection($this, $type, $data, $nextPage);
        }
        
        // Value object
        if ($data instanceof \stdClass) {
            foreach ($data as $key=>&$value) {
                $type = $key == 'user' ? 'user' : ($key == 'status' ? 'tweet' : null);
                $value = $this->convertData($value, $type);
            }
            return $data;
        }
        
        // Array
        if (is_array($data)) {
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