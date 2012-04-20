<?php
/**
 * Twitter User entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Exception;

/**
 * Autoexpending Twitter User entity.
 * 
 * @property Collection timeline               statuses/user_timeline
 * @property Collection retweeted_by_user      statuses/retweeted_by_user
 * @property Collection retweeted_to_user      statuses/retweeted_to_user
 * @property Collection followers              followers/ids
 * @property Collection friends                friends/ids
 * @property Collection incomming_friendships  friendships/incomming
 * @property Collection outgoing_friendships   friendships/outgoing
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
        $this->_stub = $stub || is_null($data) || is_scalar($value);
        
        if (is_scalar($data)) $data = $this->makeUserData($data);
        $this->setProperties($data);
    }
    

    /**
     * Get resource object for fetching subdata.
     * Preparation for a multi request.
     * 
     * @param string $item
     * @param array  $params
     * @return object
     */
    public function prepareRequest($item, array $params=array())
    {
        $method = 'GET';
        $params += $this->makeUserData(null, true);
        
        switch ($item) {
            case 'timeline':               return (object)array('resource' => 'statuses/user_timeline', 'params' => $params, 'lazy' => true);
            case 'retweeted_by_user':      return (object)array('resource' => 'statuses/retweeted_by_user', 'params' => $params, 'lazy' => true);
            case 'retweeted_to_user':      return (object)array('resource' => 'statuses/retweeted_to_user', 'params' => $params, 'lazy' => true);
            case 'followers':              return (object)array('resource' => 'followers/ids', 'params' => $params);
            case 'friends':                return (object)array('resource' => 'friends/ids', 'params' => $params);
            case 'incomming_friendships':  return (object)array('resource' => 'friendships/incomming', 'params' => $params);
            case 'outgoing_friendships':   return (object)array('resource' => 'friendships/outgoing', 'params' => $params);
        }
        
        return parent::prepareRequest($item, $params);
    }
    
    
    /**
     * Get user id/screen_name in array
     */
    public function asParams()
    {
        return self::makeUserData($this, true);
    }
    
    /**
     * Convert scalar data to value object.
     * 
     * @param mixed   $data
     * @param boolean $asParams  This is inteded as parameters
     * @return object|array
     */
    protected static function makeUserData($data, $asParams=false)
    {
        if (is_scalar($data)) {
            $key = is_int($data) || ctype_digit($data) ? 'user_id' : 'screen_name';
            $data = array($key => $data);
            return $asParams ? $data : (object)$data;
        }
        
        $data = (object)$data;
        
        if ($asParams) return property_exists($data, 'id') ? array('user_id' => $data->id) : array('screen_name' => $data->screen_name);
        return $data;
    }
}