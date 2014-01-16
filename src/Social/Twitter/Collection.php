<?php
/**
 * Twitter Collection
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Collection as Base;

/**
 * Autoexpending Twitter Collection.
 */
class Collection extends Base
{
    /**
     * Expand all stubs.
     * 
     * @param boolean $refresh  Fetch new data, even if the entity isn't a stub
     * @return Collection  $this
     */
    public function fetch($refresh=false)
    {
        if ($this->allUsers()) return $this->fetchUsers($refresh);
        return parent::fetch($refresh);
    }

    /**
     * Fetch all user entities.
     * We can get the info of up to 100 users per call.
     * 
     * @param boolean $refresh  Fetch new data, even if the entity isn't a stub
     * @return Collection  $this
     */
    private function fetchUsers($refresh)
    {
        $users = $ids = $names = array();
        
        foreach ($this as $entity) {
            if (!$refresh && $entity->isStub()) continue;
            
            if (isset($entity->id)) {
                $ids[] = $entity->id;
                $users[$entity->id] = $entity;
            } else {
                $names[] = $entity->screen_name;
                $users[$entity->screen_name] = $entity;
            }
        }

        $fn = function ($result) use (&$users) { return Me::updateUsers($result, $users); };
        
        $this->getConnection()->prepare($this);
        
        foreach (array_chunk($ids, 100) as $chunk) $this->getConnection()->post('friendships/lookup', array('user_id' => $chunk), $fn);
        foreach (array_chunk($names, 100) as $chunk) $this->getConnection()->post('friendships/lookup', array('screen_name' => $chunk), $fn);
        
        return $this->getConnection()->execute();
    }
    
    /**
     * Update user properties as callback from expandUsers()
     * 
     * @param array $result
     * @param array $users
     */
    private static function updateUsers($result, $users)
    {
        $user = isset($users[$result->id]) ? $users[$result->id] : $users[$result->screen_name];
        $user->setProperties($result);
    }
    
    /**
     * Check if all entities all Users
     * 
     * @return boolean
     */
    private function allUsers()
    {
        foreach ($this as $entity) {
            if (!$entity instanceof User) return false;
        }
        
        return true;
    }
}
