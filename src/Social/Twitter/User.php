<?php
/**
 * Twitter user entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Exception;

/**
 * Autoexpending Twitter user entity.
 * 
 * https://dev.twitter.com/docs/api/1.1/get/users/show
 * 
 * @property string     $profile_image      users/profile_image
 * @property Tweet[]    $timeline           statuses/user_timeline
 * @property Tweet[]    $retweeted_by_user  statuses/retweeted_by_user
 * @property Tweet[]    $retweeted_to_user  statuses/retweeted_to_user
 * @property User[]     $followers          followers/ids
 * @property User[]     $friends            friends/ids
 * @property User[]     $contributees       users/contributees
 * @property User[]     $contributors       users/contributors
 * @property UserList[] $lists              lists
 * @property UserList[] $subscribed_lists   lists/subscriptions
 * @property UserList[] $all_lists          lists/all
 */
class User extends Entity
{
    /**
     * Class constructor
     * 
     * @param Connection   $connection
     * @param string       $type
     * @param object|mixed $data        Data or ID/username
     * @param boolean      $stub
     */
    public function __construct(Connection $connection, $data=array(), $stub=self::NO_STUB)
    {
        $this->_connection = $connection;
        $this->_type = 'user';
        $this->_stub = is_scalar($data) ? self::STUB : $stub;
        
        if (is_scalar($data)) $data = self::makeUserData($data);
        $this->setProperties($data);
    }
    
    
    /**
     * Expand if this is a stub.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/users/show
     * 
     * @param boolean $force  Fetch new data, even if this isn't a stub
     * @return Me  $this
     */
    public function expand($force=false)
    {
        if ($force || $this->isStub()) $this->getConnection()->get('users/show', $this->asParams(), $this);
        return $this;
    }

    /**
     * Returns a map of the available size variations of the specified user's profile banner.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/users/profile_banner
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
     * @see https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
     * 
     * @param array $params
     */
    public function getTweets(array $params=array())
    {
        return $this->getConnection()->get('statuses/user_timeline', $this->asParams() + $params);
    }
    
    
    /**
     * Returns a collection of users following the specified user.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/followers/list
     * 
     * @param array $params
     * @param array $stub    Entity::STUB or Entity::NO_STUB
     * @return Collection of users
     */
    public function getFollowers(array $params=array())
    {
        return $this->getConnection()->get('followers/ids', $this->asParams() + $params);
    }
    
    /**
     * Returns a collection of with every user the specified user is following (otherwise known as their "friends").
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/friends/list
     * 
     * @param array $params
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
     * @see https://dev.twitter.com/docs/api/1.1/get/friendships/show
     * 
     * @param mixed $user        User entity/ID/username or array with users
     * @return User|Collection
     */
    public function getFriendship($user)
    {
        $key = func_num_args() >= 2 ? func_get_arg(2) : null;
        $fn = function ($result) use ($user, $key) { return User::processShowFriendship($result, $user, $key); };
        
        // Single user
        if (!is_array($user) && !$user instanceof \ArrayObject) {
            
            $results = $this->getConnection()->get('friendships/show', self::makeUserData($user, true), $fn);
            return $results[0][1];
        }
        
        // Multiple users
        $fn = function ($result) use (&$user) { return Me::processShowFriendship($result, $user); };
        $source = self::makeUserData($this, true, 'source_');

        $this->getConnection()->prepare(new Result($this->getConnection()));
        
        foreach ($user as $u) {
            $this->getConnection()->get('friendships/show', $source + self::makeUserData($u, true, 'target_'), $fn);
        }
        
        return $this->getConnection()->execute();
    }
    
    /**
     * Convert the result of friendships/lookup.
     * 
     * @param array  $result
     * @param array  $users
     * @param string $key
     * @return array
     */
    protected static function processShowFriendship($result, $users, $key)
    {
        $friendship =& $result->source;
        unset($friendship->id, $friendship->id_str, $friendship->screen_name);

        $user = !is_array($users) ? $users : (isset($users[$result->id]) ? $users[$result->id] : $users[$result->screen_name]);
        
        if (!$user instanceof User) $user = new User($this->getConnection(), array('id'=>$result->target->id_str, 'screen_name'=>$result->target->screen_name));
          elseif ($user->isStub()) $user->setProperties(array('id'=>$result->target->id_str, 'screen_name'=>$result->target->screen_name));
        
        return array($user, $key ? $friendship->$key : $friendship);
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
     * @see https://dev.twitter.com/docs/api/1.1/get/users/contributees
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
     * @see https://dev.twitter.com/docs/api/1.1/get/users/contributors
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
     * @see https://dev.twitter.com/docs/api/1.1/get/lists/list
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
     * @see https://dev.twitter.com/docs/api/1.1/get/lists/subscriptions
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
     * @see https://dev.twitter.com/docs/api/1.1/get/lists/memberships
     * 
     * @return Collection of lists
     */
    public function getListMemberships()
    {
        return $this->getConnection()->get('lists/memberships', $this->asParams());
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
}
