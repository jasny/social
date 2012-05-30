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
    public function __construct(Connection $connection, $data=array(), $stub=false)
    {
        $this->_connection = $connection;
        $this->_type = 'user';
        $this->_stub = $stub || is_scalar($data);
        
        if (is_scalar($data)) $data = self::makeUserData($data);
        $this->setProperties($data);
    }
    

    /**
     * Get resource object for fetching subdata.
     * Preparation for a multi request.
     * 
     * @param string $action  Action or fetch item
     * @param mixed  $target  Entity/id
     * @param array  $params
     * @return object
     */
    public function prepareRequest($action, $target=null, array $params=array())
    {
        switch ($action) {
            case null:                     return (object)array('resource' => 'users/show', 'params' => $this->asParams() + $params);
            case 'users/profile_image':    return (object)array('resource' => 'users/profile_image', 'params' => array('id' => null, 'screen_name' => $this->screen_name) + $params);
            
            case 'timeline':               return (object)array('resource' => 'statuses/user_timeline', 'params' => $this->asParams() + $params, 'lazy' => true);
            case 'retweeted_by_user':      return (object)array('resource' => 'statuses/retweeted_by_user', 'params' => $this->asParams() + $params, 'lazy' => true);
            case 'retweeted_to_user':      return (object)array('resource' => 'statuses/retweeted_to_user', 'params' => $this->asParams() + $params, 'lazy' => true);
            case 'followers':              return (object)array('resource' => 'followers/ids', 'params' => $this->asParams() + $params);
            case 'friends':                return (object)array('resource' => 'friends/ids', 'params' => $this->asParams() + $params);
            case 'contributees':           return (object)array('resource' => 'users/contributees', 'params' => $this->asParams() + $params);
            case 'contributors':           return (object)array('resource' => 'users/contributors', 'params' => $this->asParams() + $params);
            case 'lists':                  return (object)array('resource' => 'lists', 'params' => $this->asParams() + $params);
            case 'subscribed_lists':       return (object)array('resource' => 'lists/subscriptions', 'params' => $this->asParams() + $params);
            case 'all_lists':              return (object)array('resource' => 'lists/all', 'params' => $this->asParams() + $params);
        }
        
        return null;
    }
    
    
    /**
     * Get the relationship between users.
     * 
     * The resulting user entity/entities will have following extra properties: 'following', 'followed_by', 'notifications_enabled', 'can_dm', 'want_retweets', 'marked_spam', 'all_replies', 'blocking'.
     * 
     * @see https://dev.twitter.com/docs/api/1/get/friendships/show
     * 
     * @param mixed $user  User entity/ID/username or array with users
     * @return User|Collection
     */
    public function friendship($user)
    {
        // Single user
        if (!is_array($user) && !$user instanceof \ArrayObject) {
            $result = $this->getConnection()->get('friendships/show', self::makeUserData($this, true, 'source_') + self::makeUserData($user, true, 'target_'), false);
            $result = $result[0];

            $data =& $result->source;
            $data->id = $data->id_str = $result->target->id_str;
            $data->screen_name = $result->target->screen_name;
            
            $entity = new User($this->getConnection(), $data);
            if (is_object($user)) $entity->setProperties($user, true);
            
            return $entity;
        }
        
        // Multiple users
        $source = self::makeUserData($this, true, 'source_');
        foreach ($user as $u) {
            $requests[] = (object)array('method' => 'GET', 'url' => 'friendships/show', $source + self::makeUserData($u, true, 'target_'));
        }
        
        $entities = array();
        $results = $this->_connection->multiRequest($requests);
        
        foreach ($results as $result) {
            $data =& $result->source;
            $data->id = $data->id_str = $result->target->id_str;
            $data->screen_name = $result->target->screen_name;
            
            $entity = new User($this->getConnection(), $data);
            if (is_object($user)) $entity->setProperties($user, true);
        }

        return new Collection($this->getConnection(), 'user', $entities);
    }
    
    /**
     * Check if this user is following the specified user.
     * 
     * @param mixed $user  User entity/ID/username
     * @return boolean
     */
    public function isFollowing($user)
    {
        return $this->getConnection()->get('friendships/exists', self::makeUserData($this, true, '', '_a') + self::makeUserData($user, true, '', '_b'));
    }    
    
    /**
     * Check if this user is followed by the specified user.
     * 
     * @param mixed $user  User entity/ID/username
     * @return boolean
     */
    public function isFollowedBy($user)
    {
        return $this->getConnection()->get('friendships/exists', self::makeUserData($this, true, '', '_b') + self::makeUserData($user, true, '', '_a'));
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
        return property_exists($data, 'id') || !property_exists($data, 'sreen_name') ? array($prefix . 'user_id' . $suffix => $data->id) : array($prefix . 'screen_name' . $suffix => $data->screen_name);
    }
}