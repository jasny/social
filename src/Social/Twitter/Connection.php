<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Connection as Base;

/**
 * Twitter API connection.
 * @see https://dev.twitter.com/docs
 * @package Twitter
 * 
 * Before you start, register your application at https://dev.twitter.com/apps and retrieve a custumor key and
 *  consumer secret.
 */
class Connection extends Base implements \Social\Auth
{
    use \Social\OAuth1;

    /**
     * Name of the API service
     */
    const serviceProvider = 'twitter';
    
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
    const authURL = "https://api.twitter.com/";
    
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
     * The default file extension for API URLs
     */
    const defaultExtension = 'json';
    
    /**
     * Entity type per resource
     * @var array
     */
    protected static $resourceTypes = [
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
    ];

    /**
     * API url per resource
     * @var array
     */
    public static $resourceApi = [
        '*'                          => self::restURL,
        'oauth'                      => self::authURL,
        'statuses/update_with_media' => self::uploadURL,
        'statuses/filter'            => self::streamUrl,
        'statuses/sample'            => self::streamUrl,
        'statuses/firehose'          => self::streamUrl,
        'user'                       => self::userstreamUrl,
        'site'                       => self::sitestreamUrl,
    ];
    
    /**
     * Resource that require a multipart POST
     * @var array
     */
    protected static $resourcesMultipart = [
        'account/update_profile_background_image' => true,
        'account/update_profile_image'            => true,
        'statuses/update_with_media'              => true,
    ];
    
    /**
     * Default paramaters per resource.
     * @var array
     */
    protected static $defaultParams = [
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
    ];
    
    
    /**
     * Class constructor.
     * 
     * Passing a user id is not required to act as the user, you're only required to specify the access token and
     * secret. It can save one API call though.
     * 
     * @param string       $consumerKey     Application's consumer key
     * @param string       $consumerSecret  Application's consumer secret
     * @param array|object $access          [ token, secret, me ] or { 'token': string, 'secret': string, 'user': me }
     */
    public function __construct($consumerKey, $consumerSecret, $access=null)
    {
        $this->setCredentials($consumerKey, $consumerSecret);
        $this->setAccessInfo($access);
    }

    /**
     * Set the access info.
     * 
     * @param array|object $access  [ token, secret, me ] or { 'token': string, 'secret': string, 'user': me }
     */
    protected function setAccessInfo_entity($access)
    {
        $this->OAuth1_setAccessInfo($access);
        
        if (is_array($access) && !is_int(key($access))) $access = (object)$access;

        if ((isset($access->user_id) && (!isset($this->me) || isset($this->me->user_id)
            && $this->me->user_id != $access->user_id)) ||
            (isset($access->screen_name) && (!isset($this->me) || isset($this->me->screen_name)
            && $this->me->screen_name != $access->screen_name))
        ) {
            $user = ['id'=>@$access->user_id, 'screen_name'=>@$access->screen_name];
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
     * Build a full url.
     * 
     * @param string  $url
     * @param array   $params
     * @return string
     */
    protected function getFullUrl($url, array $params=[])
    {
        if (strpos($url, '://') === false) $url = static::getBaseUrl($url) . ltrim($url, '/');
        return self::buildUrl($url, $params);
    }
    
    /**
     * Get Twitter API URL based on de resource.
     * 
     * @param string $resource
     * @return string
     */
    protected static function getBaseUrl($url=null)
    {
        $resource = static::normalizeResource($url);
        
        if ($resource) do {
            if (isset(static::$resourceApi[$resource])) return static::$resourceApi[$resource];
            $resource = dirname($resource);
        } while ($resource != '.');

        return static::$resourceApi['*'];
    }
    
    
    /**
     * Get normalized resource from URL
     * 
     * @param string $resource
     * @return string
     */
    public static function normalizeResource($resource)
    {
        // Replace id's by '*' and remove file extension
        return preg_replace(array('~/(?:\d+|:\w+)(?=/|$)~', '~(\.\w+(\?.*)?|\?.*)$~'), array('/*', ''), $resource);
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
        return isset(self::$defaultParams[$resource]) ? self::$defaultParams[$resource] : [];
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
     * @param object $request
     * @return boolean 
     */
    protected static function detectMultipart($request)
    {
        $resource = self::normalizeResource($request->url);
        return !empty(self::$resourcesMultipart[$resource]);
    }
    
    
    /**
     * Run a single prepared HTTP request.
     * 
     * @param object|string  $request  url or value object
     * @return string
     */
    protected function singleRequest($request)
    {
        $data = parent::singleRequest($request);
        if (is_scalar($data)) return $data;

        // Follow the cursor to load all data
        if (is_object($data) && !isset($request->params['cursor']) && !empty($data->next_cursor_str)) {
            $cursor_keys = ['next_cursor', 'previous_cursor', 'next_cursor_str', 'previous_cursor_str'];
            list($key) = array_diff(array_keys((array)$data), $cursor_keys);
            
            while ($data->next_cursor_str) {
                $request->params['cursor'] = $data->next_cursor_str;
                $newdata = parent::singleRequest($request);
                
                if (!empty($newdata->$key)) $data->$key = array_merge($data->$key, $newdata->$key);
                $data->next_cursor = $newdata->next_cursor;
                $data->next_cursor_str = $newdata->next_cursor_str;
            }
        }
        
        return $data;
    }
    
    /**
     * Run multiple HTTP requests in parallel.
     * 
     * @param array $requests  array of value objects
     * @return array
     */
    protected function multiRequest(array $requests)
    {
        $results = parent::multiRequest($requests);
        $lastResults = [];

        // Follow the cursor to load all data
        do {
            $next = [];
            foreach ($results as $i=>&$data) {
                $has_cursor = isset($lastResults[$i]) || !isset($requests[$i]->params['cursor']);
                if ($has_cursor && is_object($data) && !empty($data->next_cursor_str)) {
                    $next[$i] = $requests[$i];
                    $next[$i]->params['cursor'] = $data->next_cursor_str;
                }
            }
            
            if (!$next) break;
            
            $lastResults = parent::multiRequest($requests);

            foreach ($lastResults as $i=>$newdata) {
                $data =& $results[$i];
                
                // Something went wrong, let's not go into an endless loop
                if (!is_object($newdata)) {
                    $results[$i] = null;
                    continue;
                }
                
                $cursor_keys = ['next_cursor', 'previous_cursor', 'next_cursor_str', 'previous_cursor_str'];
                list($key) = array_diff(array_keys(get_object_vars($data)), $cursor_keys);
                
                if (!empty($newdata->$key)) $data->$key = array_merge($data->$key, $newdata->$key);
                $data->next_cursor = $newdata->next_cursor;
                $data->next_cursor_str = $newdata->next_cursor_str;
            }
        } while (true); // breaks above
        
        return $results;
    }

    
    /**
     * Get authentication url.
     * Temporary accesss information is automatically stored to a session.
     *
     * @param string $level      'authorize' or 'authenticate'
     * @param string $returnUrl  The URL to return to after successfully authenticating.
     * @return string
     */
    protected function getAuthUrl($level, $returnUrl=null)
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
        
        return $this->getFullUrl("oauth/$level", ['oauth_token'=>$tmpAccess['oauth_token']]);
    }

    /**
     * Authenticate
     * 
     * @param string $level   'authorize' or 'authenticate'
     */
    public function auth($level='authorize')
    {
        if ($this->isAuth()) return;
        
        if (isset($_GET['oauth_verifier'])) {
            $this->handleAuthResponse();
            return self::redirect($this->getCurrentUrl());
        }
  
        self::redirect($this->getAuthUrl($level));
    }
    
    /**
     * Get error from HTTP result.
     * 
     * @param int   $httpcode
     * @param mixed $result
     * @return string
     */
    static protected function httpError($httpcode, $result)
    {
        if (is_scalar($result)) return $httpcode . ' - ' . $result;
        return parent::httpError($httpcode, $result);
    }
    
    
    /**
     * Stream content from Twitter.
     * 
     * @param callback $writefunction  Stream content to this function
     * @param string   $resource
     * @param array    $params         Request parameters
     * @return boolean
     */
    public function stream($writefunction, $resource, array $params=[])
    {
        $method = $resource == 'statuses/filter' ? 'POST' : 'GET';
        $request = (object)['url'=>$resource, 'params'=>$params, 'method'=>$method, 'writefunction'=>$writefunction];
        
        return $this->singleRequest($request);
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
    public function search($query, array $params=[])
    {
        $params['q'] = $query;
        return $this->get('search/tweets', $params);
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
    public function searchUsers($query, array $params=[])
    {
        $params['q'] = $query;
        return $this->get('users/search', $params);
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
}
