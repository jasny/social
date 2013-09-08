<?php
/**
 * Jasny Social
 * World's best PHP library for Social APIs
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
 * Before you start, register your application at https://dev.twitter.com/apps and retrieve a custumor key and consumer secret.
 */
class Connection extends Base implements \Social\Auth
{
    use \Social\OAuth1, \Social\EntityMapping;

    /**
     * Name of the API service
     */
    const apiName = 'twitter';
    
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
    private static $resourceTypes = [
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
        'oauth'                      => self::oauthURL,
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
    private static $resourcesMultipart = [
        'account/update_profile_background_image' => true,
        'account/update_profile_image'            => true,
        'statuses/update_with_media'              => true,
    ];
    
    /**
     * Default paramaters per resource.
     * @var array
     */
    private static $defaultParams = [
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
     * @param string          $consumerKey     Application's consumer key
     * @param string          $consumerSecret  Application's consumer secret
     * @param string|object   $access          [ user's access token, user's secret, user ] or { 'token': string, 'secret': string, 'user': twitter id }
     */
    public function __construct($consumerKey, $consumerSecret, $access=null)
    {
        $this->setCredentials($consumerKey, $consumerSecret);
        $this->setAccessInfo($access);
        
        $this->defaultExtension = 'json';
    }

    /**
     * Get Twitter API URL based on de resource.
     * 
     * @param string $resource
     * @return string
     */
    protected static function getBaseUrl($resource=null)
    {
        $resource = self::normalizeResource($resource);
        
        if ($resource) do {
            if (isset(self::$resourceApi[$resource])) return self::$resourceApi[$resource];
            $resource = dirname($resource);
        } while ($resource != '.');

        return self::$resourceApi['*'];
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
     * @param string $resource
     * @return boolean 
     */
    protected static function detectMultipart($resource)
    {
        $resource = self::normalizeResource($resource);
        return !empty(self::$resourcesMultipart[$resource]);
    }
    
    
    /**
     * Run a single prepared HTTP request.
     * 
     * @param object|string  $request  url or { 'method': string, 'url': string, 'params': array, 'headers': array, 'convert': mixed, 'writefunction': callback  }
     * @return string
     */
    protected function singleRequest($request)
    {
        $data = parent::singleRequest($request);
        if (is_scalar($data)) return $data;

        // Follow the cursor to load all data
        if (is_object($data) && !isset($request->params['cursor']) && !empty($data->next_cursor_str)) {
            list($key) = array_diff(array_keys((array)$data), array('next_cursor', 'previous_cursor', 'next_cursor_str', 'previous_cursor_str'));
            
            while ($data->next_cursor_str) {
                $request->params['cursor'] = $data->next_cursor_str;
                $newdata = parent::singleRequest($request);
                
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
        $results = parent::multiRequest($requests);
        $lastResults = [];

        // Follow the cursor to load all data
        do {
            $next = [];
            foreach ($results as $i=>&$data) {
                if (is_object($data) && (isset($lastResults[$i]) || !isset($requests[$i]->params['cursor'])) && !empty($data->next_cursor_str)) {
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
                
                list($key) = array_diff(array_keys(get_object_vars($data)), array('next_cursor', 'previous_cursor', 'next_cursor_str', 'previous_cursor_str'));
                
                if (!empty($newdata->$key)) $data->$key = array_merge($data->$key, $newdata->$key);
                $data->next_cursor = $newdata->next_cursor;
                $data->next_cursor_str = $newdata->next_cursor_str;
            }
        } while (true); // breaks above
        
        foreach ($results as $i=>&$data) {
            $data = $this->convertResponse($requests[$i], $data);
        }
        
        return $results;
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
     * @param boolean $convert  Convert to collection, false returns raw data
     * @return Collection  of Tweets
     */
    public function search($query, array $params=[], $convert=true)
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
    public function searchUsers($query, array $params=[], $convert=true)
    {
        $params['q'] = $query;
        return $this->get('users/search', $params, $convert);
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
     * @param string   $type     Entity type, true is autodetect
     * @param boolean  $stub     If an Entity, asume it's a stub
     * @param object   $request  Request used to get this data
     * @return Entity|Collection|DateTime|mixed
     */
    public function convert($data, $type=null, $stub=Entity::NO_STUB, $request=null)
    {
        if ($type === true) $type = $this->detectType($request->url);
        
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
                $value = $this->convert($value, $type);
            }
            return $data;
        }
        
        // Array
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = $this->convert($value);
            }
            return $data;
        }
        
        // Probably some other kind of object
        return $data;
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
        $id[$i]--;
        
        return $id;
    }
}
