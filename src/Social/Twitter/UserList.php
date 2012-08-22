<?php
/**
 * Twitter user list entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Exception;

/**
 * Autoexpending Twitter user list entity.
 * 
 * https://dev.twitter.com/docs/api/1/get/lists/show
 * 
 * @property Tweet[]  $tweets        lists/statuses
 * @property User[]   $subscribers   lists/subscribers
 */
class UserList extends Entity
{
    /**
     * Get resource object for fetching subdata.
     * Preparation for a multi request.
     * 
     * @param string $action
     * @param mixed  $target  Entity/id
     * @param array  $params
     * @return object
     */
    public function prepareRequest($action, $target=null, array $params=array())
    {
        $params = $this->asParams() + $params;
        
        switch ($action) {
            case null:          return (object)array('resource' => 'lists/show');
            
            case 'tweets':      return (object)array('resource' => 'lists/statuses', 'params' => $params);
            case 'subscribers': return (object)array('resource' => 'lists/subscribers', 'params' => $params);
                
            case 'add_member':  return (object)array('method' => 'POST', 'resource' => 'lists/members/create', 'params' => User::makeUserData($target, true) + $params);
        }
        
        return parent::prepareRequest($item, $params);
    }
    
    
    /**
     * Get user id/screen_name in array.
     * 
     * @return array
     */
    public function asParams()
    {
        if (isset($this->id)) return array('list_id' => $this->id);
        
        if (isset($this->user) && isset($this->slug)) {
            if (is_scalar($this->user)) {
                $key = is_int($this->user) || ctype_digit($this->user) ? 'owner_id' : 'owner_screen_name';
                $data = array($key => $data, 'slug'=>$this->slug);
            }
        }
        
        throw new Exception("Unknown list: id is unknown and user+slug is also unknown");
    }
    
    
    /**
     * Add member(s) to the list.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/lists/members/create
     * @see https://dev.twitter.com/docs/api/1/post/lists/members/create_all
     * 
     * @param mixed $user  User entity/ID/username or array with users
     * @return User|Collection
     */
    public function addMember($user)
    {
        // Single user
        if (!is_array($user) && !$user instanceof \ArrayObject) {
            return $this->getConnection()->get('lists/members/create', $this->makeUserData($user, true));
        }
        
        // Multiple users (1 request per 500 users)
        foreach ($user as $u) {
            if (is_object($u)) $key = property_exists($u, 'id') ? 'id' : 'screen_name';
              else $key = is_int($u) || ctype_digit($u) ? 'id' : 'screen_name';
            
            if ($key == 'id') {
                if (is_object($u)) {
                    $ids[] = $u->id;
                    $users[$u->id] = $u;
                } else {
                    $ids[] = $u;
                }

                if (count($ids) >= 100) {
                    $requests[] = (object)array('method' => 'POST', 'url' => 'lists/members/create_all', array('user_id' => $ids));
                    $ids = array();
                }

            } else {
                if (is_object($u)) {
                    $names[] = $u->screen_name;
                    $users[$u->screen_name] = $u;
                } else {
                    $names[] = $u;
                }

                if (count($names) >= 500) {
                    $requests[] = (object)array('method' => 'POST', 'url' => 'lists/members/create_all', array('screen_name' => $names));
                    $names = array();
                }
            }
        }

        if (!empty($ids)) $requests[] = (object)array('method' => 'POST', 'url' => 'lists/members/create_all', array('user_id' => $ids) + $params);
        if (!empty($names)) $requests[] = (object)array('method' => 'POST', 'url' => 'lists/members/create_all', array('screen_name' => $names) + $params);
        
        $users = array();
        $results = $this->_connection->multiRequest($requests);
        
        foreach ($results as $result) {
            foreach ($result as $user) {
                $user->following = in_array('following', $user->connections);
                $user->following_requested = in_array('following_requested', $user->connections);
                $user->followed_by = in_array('followed_by', $user->connections);
                
                if (isset($users[$user->id])) $user->setProperties($users[$user->id], true);
                  elseif (isset($users[$user->screen_name])) $user->setProperties($users[$user->screen_name], true);
                
                $users[] = $user;
            }
        }

        return new Collection($this->getConnection(), 'user', $users);
    }
}