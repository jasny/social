<?php
/**
 * Twitter Collection
 * 
 * @license MIT
 * @copyright 2012-2014 Jasny
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
     * @return Collection  $this
     */
    public function hydrate()
    {
        if ($this->allUsers()) return $this->fetchUsers(false);
        return parent::hydrate();
    }

    /**
     * Refresh all entities.
     * 
     * @return Collection  $this
     */
    public function refresh()
    {
        if ($this->allUsers()) return $this->fetchUsers(true);
        return parent::refresh();
    }

    
    /**
     * Check if all entities all Users
     * 
     * @return boolean
     */
    protected function allUsers()
    {
        foreach ($this as $entity) {
            if (!$entity instanceof User) return false;
        }
        
        return true;
    }
    
    /**
     * Fetch all user entities.
     * We can get the info of up to 100 users per call.
     * 
     * @param boolean $refresh  Fetch new data, even if the entity isn't a stub
     * @return Collection  $this
     */
    protected function fetchUsers($refresh)
    {
        $chunks = [];
        $i = 0;
        foreach ($this as $entity) {
            if (!$refresh && $entity->isStub()) continue;
            
            if ($i % 100 == 0) $chunk = (object)['user_id'=>[], 'screen_name'=>[]];
            if (isset($entity->id)) $chunk->user_id[] = $entity->id;
             elseif (isset($entity->screen_name)) $chunk->screen_name[] = $entity->screen_name;
            if (++$i % 100 == 0) $chunks[] = $chunk;
        }

        $this->getConnection()->prepare($this);
        
        foreach (array_chunk($users, 100, true) as $chunk) {
            $this->post('friendships/lookup', ['user_id'=>$chunk->user_id, 'screen_name'=>$chunk->screen_name]);
        }
        
        return $this->getConnection()->execute();
    }
    
    /**
     * Update user properties as callback from expandUsers()
     * 
     * @param array $result
     * @param array $users
     */
    protected static function updateUsers($result, $users)
    {
        $user = isset($users[$result->id]) ? $users[$result->id] : $users[$result->screen_name];
        $user->setProperties($result);
    }
}
