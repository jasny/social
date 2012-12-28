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
 * https://dev.twitter.com/docs/api/1/get/users/show
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
     * Get resource object for fetching subdata.
     * Preparation for a multi request.
     * 
     * @param string $action  Action or fetch item
     * @param mixed  $target  Not used!
     * @param array  $params
     * @return object
     */
    public function _prepareRequest($action, $target=null, array $params=array())
    {
        switch ($action) {
            case null:                     return (object)array('resource' => 'users/show', 'params' => $this->asParams() + $params);
            case 'profile_image':          return (object)array('resource' => 'users/profile_image', 'params' => array('id' => null, 'screen_name' => $this->screen_name) + $params);
            
            case 'timeline':               return (object)array('resource' => 'statuses/user_timeline', 'params' => $this->asParams() + $params, 'lazy' => true);
            case 'retweeted_by_user':      return (object)array('resource' => 'statuses/retweeted_by_user', 'params' => $this->asParams() + $params, 'lazy' => true);
            case 'retweeted_to_user':      return (object)array('resource' => 'statuses/retweeted_to_user', 'params' => $this->asParams() + $params, 'lazy' => true);
            case 'followers':              return (object)array('resource' => 'followers/ids', 'params' => $this->asParams() + $params);
            case 'friends':                return (object)array('resource' => 'friends/ids', 'params' => $this->asParams() + $params);
            case 'contributees':           return (object)array('resource' => 'users/contributees', 'params' => $this->asParams() + $params);
            case 'contributors':           return (object)array('resource' => 'users/contributors', 'params' => $this->asParams() + $params);
            case 'lists':                  return (object)array('resource' => 'lists', 'params' => $this->asParams() + $params);
            case 'all_lists':              return (object)array('resource' => 'lists/all', 'params' => $this->asParams() + $params);
            case 'subscribed_lists':       return (object)array('resource' => 'lists/subscriptions', 'params' => $this->asParams() + $params);
            case 'list_memberships':       return (object)array('resource' => 'lists/memberships', 'params' => $this->asParams() + $params);
        }
        
        return null;
    }
    
    
    /**
     * Expand if this is a stub.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/account/verify_credentials
     * 
     * @param boolean $force  Fetch new data, even if this isn't a stub
     * @return Me  $this
     */
    public function expand($force=false)
    {
        if ($force || $this->isStub()) $this->getConnection()->get('account/verify_credentials', array(), $this);
        return $this;
    }

    
    /**
     * Get the relationship between users.
     * 
     * The resulting user entity/entities will have following extra properties: 'following', 'followed_by', 'notifications_enabled', 'can_dm', 'want_retweets', 'marked_spam', 'all_replies', 'blocking'.
     * 
     * @see https://dev.twitter.com/docs/api/1/get/friendships/show
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
