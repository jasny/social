<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Twitter;

/**
 * Entity representing a user
 * 
 * @package Twitter
 */
class User extends Entity implements \Social\User, \Social\Profile
{
    /**
     * Class constructor
     * 
     * @param object|string $data        Data or ID/username
     * @param boolean       $stub
     * @param Connection    $connection
     */
    public function __construct($data=[], $stub=self::STUB, $connection=null)
    {
        $this->_connection = $connection;
        $this->_stub = is_scalar($data) ? self::STUB : $stub;
        
        // Data should be set for any user except Me
        if (isset($data)) {
            if (is_scalar($data)) $data = self::makeUserData($data);
            $this->setProperties($data);
        }
    }
        
    /**
     * Cast some of the data to entities
     */
    protected function cast()
    {
        if (isset($this->location) && !$this->location instanceof Location)
            $this->location = new Location($this->location);
    }
    
    /**
     * Check if this user is the same as the given one.
     * 
     * @param User|object|string $user  User entity, id or screen name
     * @return boolean
     */
    public function is($user)
    {
        if ($user instanceof Entity && !$user instanceof User) return false;
        
        if (is_scalar($user)) {
            $key = is_int($user) || ctype_digit($user) ? 'id' : 'screen_name';
            if (!isset($this->$key)) return null; // Don't know
            
            return $this->$key == $user;
        }
        
        if (is_array($user)) $user = (object)$user;
        
        if (isset($this->id) && isset($user->id)) return $this->id == $user->id;
        if (isset($this->screen_name) && isset($user->screen_name)) return $this->screen_name == $user->screen_name;
        
        return null; // Don't know
    }
    
    
    /**
     * Get user id/screen_name in array.
     * 
     * @return array
     */
    public function asParams()
    {
        return self::makeUserData($this, true);
    }
    
    /**
     * Convert scalar data to value object.
     * 
     * @ignore Internal use only.
     * 
     * @param mixed   $data
     * @param boolean $asParams  This is inteded as parameters
     * @param string  $prefix    Parameter key prefix
     * @param string  $suffix    Parameter key suffix
     * @return object|array
     */
    public static function makeUserData($data, $asParams=false, $prefix='', $suffix='')
    {
        if (is_scalar($data)) {
            $key = is_int($data) || ctype_digit($data) ? 'user_id' : 'screen_name';
            $data = array($prefix . $key . $suffix => $data);
            return $asParams ? $data : (object)$data;
        }
        
        $data = (object)$data;
        if (!$asParams) return $data;
        return property_exists($data, 'id') || !property_exists($data, 'screen_name') ? array($prefix . 'user_id' . $suffix => $data->id) : array($prefix . 'screen_name' . $suffix => $data->screen_name);
    }
    
    
    /**
     * Get the user at another service provider.
     * 
     * @param \Social\Connection|string $service  Service provider
     * @return null
     */
    public function atProvider($service)
    {
        return null;
    }
    
    /**
     * Get the unique identifier of the entity.
     * 
     * @return mixed
     */
    public function getId()
    {
        return !isset($this->id) && isset($this->screen_name) ? $this->screen_name : $this->id;
    }
    
    /**
     * Get user's full name
     * 
     * @return string
     */
    public function getName()
    {
        return isset($this->name) ? $this->name : null;
    }
    
    /**
     * Get url to user's locale (= language)
     * 
     * @return string
     */
    public function getLocale()
    {
        return isset($this->lang) ? $this->lang : null;
    }
    
    /**
     * Get user's timezone
     * 
     * @return string
     */
    public function getTimezone()
    {
        return isset($this->time_zone) ? $this->time_zone : null;
    }
    
    
    /**
     * Get username on Facebook.
     * 
     * @return string
     */
    public function getUsername()
    {
        return isset($this->screen_name) ? $this->screen_name : null;
    }
    
    /**
     * Get URL to profile
     * 
     * @return string
     */
    public function getLink()
    {
        return isset($this->screen_name) ? "http://www.twitter.com/{$this->screen_name}" : null;
    }
    
    /**
     * Get url to profile picture.
     * 
     * If size is omited the largest available image is returned.
     * $size is an just an indication, the image may not have those exact dimensions.
     * 
     * @todo If a $size is square, return one of the smaller images.
     * 
     * @param string $size   'normal', 'bigger' or 'mini', other values will return the original size.
     * @return string
     */
    public function getPicture($size=null)
    {
        if (!empty($this->default_profile_image)) return null; // Don't return twitter's default image
        
        if (!isset($this->profile_image_url) && !isset($this->profile_image_url_https)) return null;
        
        $url = isset($this->profile_image_url_https) && !empty($_SERVER['HTTPS']) ?
            $this->profile_image_url_https : $this->profile_image_url;
        
        $suffix = $size === 'normal' || $size === 'bigger' || $size === 'mini' ? "_$size" : '';
        return preg_replace('/_normal(\.\w+)/', $suffix . '$1', $url);
    }
    
    
    /**
     * Get user's email address
     * 
     * @return string
     */
    public function getEmail()
    {
        return isset($this->email) ? $this->email : null;
    }
    
    /**
     * Get user's website.
     * Return the main (or first) website, if multiple are known.
     * 
     * @todo Expand t.co url
     *
     * @return string
     */
    public function getWebsite()
    {
        return isset($this->url) ? $this->url : null;
    }
    
    /**
     * Get user's address
     * 
     * @return null
     */
    public function getLocation()
    {
        return isset($this->location) ? $this->location : null;
    }
    
    
    /**
     * Get user's bio
     * 
     * @return null
     */
    public function getDescription()
    {
        return isset($this->description) ? $this->description : null;
    }
    
    /**
     * Get user's employment
     * 
     * @return null
     */
    public function getEmployment()
    {
        return null;
    }
    
    /**
     * Get user's employment company.
     * 
     * @return null
     */
    public function getCompany()
    {
        return null;
    }
    
    
    /**
     * Cast object to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getName() ?: (string)$this->getUsername();
    }
    
    
    /**
     * Fetch data of this user.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/users/show
     * 
     * @return User $this
     */
    public function refresh()
    {
        $this->getConnection()->get('users/show', $this->asParams(), $this);
        return $this;
    }

    /**
     * Returns a map of the available size variations of the specified user's profile banner.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/users/profile_banner
     * 
     * @return object
     */
    public function getProfileBanner()
    {
        return $this->getConnection()->get('users/profile_banner', $this->asParams());
    }
    
    
    /**
     * Returns a collection of the most recent Tweets posted by the user.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
     * 
     * @param array $params
     * @return Collection of tweets
     */
    public function getTweets(array $params=array())
    {
        return $this->getConnection()->get('statuses/user_timeline', $this->asParams() + $params);
    }
    
    
    /**
     * Returns a collection of users following the specified user.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/followers/list
     * 
     * @param array $params
     * @return Collection of users
     */
    public function getFollowers(array $params=array())
    {
        return $this->getConnection()->get('followers/ids', $this->asParams() + $params);
    }
    
    /**
     * Returns a collection of with every user the specified user is following (otherwise known as their "friends").
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/friends/list
     * 
     * @param array $params
     * @return Collection of users
     */
    public function getFriends(array $params=array())
    {
        return $this->getConnection()->get('friends/ids', $this->asParams() + $params);
    }
    
    /**
     * Get the relationship between users.
     * 
     * The resulting user entity/entities will have following extra properties: 'following', 'followed_by', 'notifications_enabled', 'can_dm', 'want_retweets', 'marked_spam', 'all_replies', 'blocking'.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/friendships/show
     * 
     * @param mixed $user        User entity/ID/username or array with users
     * @return User|Collection
     */
    public function getFriendship($user)
    {
        $key = func_num_args() >= 2 ? func_get_arg(2) : null;
        
        // Single user
        if (!is_array($user) && !$user instanceof \ArrayObject) {
            $fn = function ($result) use ($user, $key) { return User::processShowFriendship($result, $user, $key); };
            
            $results = $this->getConnection()->get('friendships/show', self::makeUserData($this, true, 'source_') + self::makeUserData($user, true, 'target_'), $fn);
            return $results[0];
        }
        
        // Multiple users
        $users = $user;

        foreach ($users as &$user) {
            if (!$user instanceof self) $user = new User($this->connection, $user, Entity::STUB);
        }

        $this->getConnection()->prepare(new Result($this->getConnection(), $users));
        $fn = function ($result, $i) use ($users, $key) { return User::processShowFriendship($result, $users[$i], $key); };
        
        $source = self::makeUserData($this, true, 'source_');
        foreach ($users as $user) {
            $this->getConnection()->get('friendships/show', $source + self::makeUserData($user, true, 'target_'), $fn);
        }
        
        return $this->getConnection()->execute();
    }
    
    /**
     * Convert the result of friendships/lookup.
     * 
     * @param object $result
     * @param User   $user
     * @param string $key
     * @return array
     */
    protected static function processShowFriendship($result, $user, $key)
    {
        $friendship =& $result->source;
        unset($friendship->id, $friendship->id_str, $friendship->screen_name);

        if ($user->isStub()) $user->setProperties(array('id'=>$result->target->id_str, 'screen_name'=>$result->target->screen_name));
        
        return $key ? $friendship->$key : $friendship;
    }
    
    /**
     * Check if this user is following the specified user.
     * 
     * @param mixed $user  User entity/ID/username or array with users
     * @return boolean|array
     */
    public function isFollowing($user)
    {
        return $this->getFriendship($user, 'following');
    }    
    
    /**
     * Check if this user is followed by the specified user.
     * 
     * @param mixed $user  User entity/ID/username
     * @return boolean
     */
    public function isFollowedBy($user)
    {
        return $this->getFriendship($user, 'followed_by');
    }
    
    
    /**
     * Returns a collection of users that the specified user can "contribute" to.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/users/contributees
     * 
     * @param array $params
     * @return Collection of users
     */
    public function getContributees(array $params=array())
    {
        $this->getConnection()->get('users/contributees', $this->asParams() + $params);
    }
    
    /**
     * Returns a collection of users who can contribute to the specified account.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/users/contributors
     * 
     * @param array $params
     * @return Collection of users
     */
    public function getContributors(array $params=array())
    {
        $this->getConnection()->get('users/contributors', $this->asParams() + $params);
    }
    
    
    /**
     * Returns all lists the authenticating or specified user subscribes to, including their own.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/lists/list
     * 
     * @return Collection of lists
     */
    public function getLists()
    {
        return $this->getConnection()->get('lists/list', $this->asParams());
    }
    
    /**
     * Obtain a collection of the lists the specified user is subscribed to. Does not include the user's own lists.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/lists/subscriptions
     * 
     * @return Collection of lists
     */
    public function getListSubscriptions()
    {
        return $this->getConnection()->get('lists/subscriptions', $this->asParams());
    }
    
    /**
     * Returns the lists the specified user has been added to.
     * 
     * @link https://dev.twitter.com/docs/api/1.1/get/lists/memberships
     * 
     * @return Collection of lists
     */
    public function getListMemberships()
    {
        return $this->getConnection()->get('lists/memberships', $this->asParams());
    }
}
