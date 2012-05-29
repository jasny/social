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
        
        if (is_scalar($data)) $data = $this->makeUserData($data);
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
     * @return object|array
     */
    public static function makeUserData($data, $asParams=false)
    {
        if (is_scalar($data)) {
            $key = is_int($data) || ctype_digit($data) ? 'user_id' : 'screen_name';
            $data = array($key => $data);
            return $asParams ? $data : (object)$data;
        }
        
        $data = (object)$data;
        
        if ($asParams) return property_exists($data, 'id') || !property_exists($data, 'sreen_name')  ? array('user_id' => $data->id) : array('screen_name' => $data->screen_name);
        return $data;
    }
}