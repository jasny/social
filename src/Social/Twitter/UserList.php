<?php
/**
 * Twitter user list entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

/**
 * Twitter user list entity.
 */
class UserList extends Entity
{
    /**
     * Expand if this is a stub.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/statuses/show/%3Aid
     * 
     * @param boolean $force  Fetch new data, even if this isn't a stub
     * @return Tweet $this
     */
    public function expand($force=false)
    {
        if ($force || $this->isStub()) $this->getConnection()->get('lists/show', $this->asParams(), $this);
        return $this;
    }
    
    
    /**
     * Returns tweet timeline for members of the specified list.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/lists/subscribers
     * 
     * @params array $params
     * @return Collection of tweets
     */
    public function getTweets(array $params=array())
    {
        return $this->getConnection()->get('lists/statuses', $this->asParams() + $params);
    }
    
    /**
     * Returns the subscribers of the specified list.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/lists/subscribers
     * 
     * @params array $params
     * @return Collection of users
     */
    public function getSubscribers(array $params=array())
    {
        return $this->getConnection()->get('lists/subscribers', $this->asParams() + $params);
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
            return $this->getConnection()->get('lists/members/create', User::makeUserData($user, true), $this);
        }
        
        // Multiple users (1 request per 100 users)
        $ids = $names = array();
        
        foreach ($this as $entity) {
            if (isset($entity->id)) $ids[] = $entity->id;
             else $names[] = $entity->screen_name;
        }

        $this->getConnection()->prepare($this);
        
        foreach (array_chunk($ids, 100) as $chunk) $this->getConnection()->post('lists/members/create_all', array('user_id' => $chunk), $fn);
        foreach (array_chunk($names, 100) as $chunk) $this->getConnection()->post('lists/members/create_all', array('screen_name' => $chunk), $fn);
        
        return $this->getConnection()->execute();
    }
    
    
    /**
     * Compare if lists are the same. 
     * 
     * @param UserList|string $list
     */
    public function is($list)
    {
        if (is_scalar($list)) $list = (object)array('id'=>$list);
         elseif (is_array($list)) $list = (object)$list;
        
        if (isset($this->id) && isset($list->id)) return $this->id == $list->id;

        if (isset($this->slug) && isset($list->slug) && $this->slug != $list->slug) return false;
        
        if (isset($this->user) && isset($list->user)) {
            if (is_scalar($this->user) && is_scalar($list->user)) {
                return $this->user == $list->user; // Might be incorrect when comparing a user id with a screen name
            } elseif (is_object($this->user)) {
                return $this->user->is($list->user);
            } else {
                return $list->user->is($this->user);
            }
        }
        
        throw new Exception("Unable to compare lists: can't compare list id with user+slug.");
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
                return array($key => $this->user, 'slug'=>$this->slug);
            }
        }
        
        throw new Exception("Unknown list: id is unknown and user+slug is also unknown");
    }
}
