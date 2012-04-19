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
     * Twitter API URL
     */
    const baseURL = "https://api.twitter.com/1/";

    /**
     * Entity type per resource
     * @var array
     */
    public static final $resourceTypes = array(
        'statuses' => 'status',
        'statuses/*/retweeted_by' => 'user',
        'statuses/oembed' => null,
        'direct_messages~' => 'direct_message',
        'followers' => 'user',
        'friends' => 'user',
        'friendships' => 'user',
        'friendships/exists' => null,
        'users' => 'user',
        'users/suggestions' => null,
        'users/profile_image' => null,
        'favorites' => 'status',
        'lists' => 'list',
        'lists/statuses' => 'status',
        'lists/members' => 'user',
        'account' => 'me',
        'account/rate_limit_status' => null,
        'account/totals' => null,
        'account/settings' => null,
        'notifications' => 'user',
        'saved_searches' => 'saved_search',
        'geo' => 'place',
        'geo/reverse_geocode' => null,
        'blocks' => 'user',
        'report_spam' => 'user',
    );
    
    /**
     * Default paramaters per resource.
     * @var array
     */
    public static final $defaultParams = array(
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
              else throw new Exception("Was expecting an ID (int) or Twitter\\Me entity for \$user, but got a " . (is_object($user) ? get_class($user) : get_type($user)));
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
     * Get Twitter API URL.
     * 
     * @return string
     */
    protected function getBaseUrl()
    {
        return self::baseURL;
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
        $callbackUrl = $this->getCurrentUrl($callbackUrl, array('twitter_auth' => 'auth'));
        return parent::getAuthUrl($level, $callbackUrl, $tmpAccess);
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
        if (!isset($params['twitter_auth'])) $params['twitter_auth'] = null;
        $params['oauth_token'] = null;
        $params['oauth_verifier'] = null;

        return parent::getCurrentUrl($page, $params);
    }
    
    /**
     * Get default parameters for resource.
     * 
     * @param string $resource
     * @return array
     */
    protected function getDefaultParams($resource)
    {
        $resource = parse_url($resource, PHP_URL_PATH);
        return isset(self::$defaultParams[$resource]) ? self::$defaultParams[$resource] : array();
    }

    /**
     * Get entity type for resource.
     * 
     * @param type $resource 
     * @return string
     */
    protected function detectType($resource)
    {
        $resource = parse_url($resource, PHP_URL_PATH);
        $resource = preg_replace('~/\d+(?=/|$)~', '/*', $resource); // Replace id's by '*'
        
        do {
            if (isset(self::$resourceTypes[$resource])) return self::$resourceTypes[$resource];
            $resource = dirname($resource);
        } while ($resource != '.');
        
        return null;
    }
    
    
    /**
     * Get raw data from Twitter.
     * 
     * @param string $resource
     * @param array  $params    GET parameters
     * @return mixed
     */
    public function getData($resource, array $params=array())
    {
        $params += $this->getDefaultParams($resource);
        
        $response = $this->httpRequest('GET', $resource . (pathinfo($id, PATHINFO_EXTENSION) ? "" : ".json"), $params);
        $data = json_decode($response);
        return $data ?: $response;
    }

    /**
     * Fetch an entity (or other data) from Twitter.
     * 
     * @param string $resource
     * @param array  $params
     * @return Entity
     */
    public function get($resource, array $params=array())
    {
        $type = $this->detectType($resource);
        
        $data = $this->getData($resource . ".json", $params);
        return $this->convertData($data, $type, false, $this->buildUrl($id, $params));
    }
    
    /**
     * Post to Twitter and return raw data.
     * 
     * @param string $resource
     * @param array  $params    POST parameters
     * @return mixed
     */
    public function postData($resource, array $params)
    {
        $params += $this->getDefaultParams($resource);
        
        $response = $this->httpRequest('POST', $resource . (pathinfo($id, PATHINFO_EXTENSION) ? "" : ".json"), $params);
        $data = json_decode($response);
        return $data ?: $response;
    }
    
    /**
     * Post to Twitter and return entity.
     * 
     * @param string $resource
     * @param array  $params    POST parameters
     * @return mixed
     */
    public function post($resource, array $params)
    {
        $type = $this->detectType($resource);
        
        $data = $this->getData($resource . ".json", $params);
        return $this->convertData($data, $type, false, $this->buildUrl($id, $params));
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
        
        $this->me = new Me($this, null, true);
        return $this->me;
    }
    
    
    /**
     * Factory method for new entities.
     * 
     * @param string  $type
     * @param object  $data
     * @param boolean $stub
     * @return Entity
     */
    protected function createEntity($type, $data, $stub=false)
    {
        if ($type != 'user' && $type != 'status' && $type != 'direct_message' && $type != 'list' && $type != 'saved_search' && $type != 'place') {
            throw new Exception("Unable to create a Twitter entity: unknown entity type '$type'");
        }
        
        $type = str_replace('_', '', $type);
        return new $type($this, $data, $stub);
    }
    
    /**
     * Create a new entity
     * 
     * @param string $type
     * @param array  $data
     * @return Entity
     */
    public function create($type, $data=array())
    {
        return $this->createEntity($type, (object)$data);
    }
    
    /**
     * Create a new collection.
     * 
     * @param string $type      Type of entities in the collection (may be omitted)
     * @param array  $data
     * @return Collection
     */
    public function collection($type=null, $data=array())
    {
        if (is_array($type)) {
            $data = $type;
            $type = null;
        }
        
        return new Collection($this, $type, $data, $nextPage);
    }
    
    /**
     * Create a stub.
     * 
     * @param string       $type  Entity type
     * @param array|string $data  Data or id
     * @return Entity
     */
    public function stub($type, $data)
    {
        if (is_array($data)) $data = (object)$data;
        return $this->createEntity($type, $data, true);
    }
    
    
    /**
     * Convert data to Entity, Collection or DateTime.
     * 
     * @param mixed    $data
     * @param string   $type    Entity type
     * @param boolean  $stub    If an Entity, asume it's a stub
     * @param string   $source  URL used to get data
     * @return Entity|Collection|DateTime|mixed
     */
    public function convertData($data, $type=null, $stub=false, $source=null)
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
        if ($data instanceof \stdClass && isset($data->id)) return $this->create($this, $type, $data, $stub);
        
        // Collection
        if ($data instanceof \stdClass && isset($data->next_cursor)) {
            
            return new Collection($this, $type, $data->data, $nextPage);
        }

        if (is_array($data) && is_object(reset($data))) {
            
            return new Collection($this, $type, $data, $nextPage);
        }
        
        // Value object
        if ($data instanceof \stdClass) {
            foreach ($data as $key=>&$value) {
                $value = $this->convertData($value, $key == 'user' ? 'user' : null);
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