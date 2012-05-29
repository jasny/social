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
use Social\Exception;

/**
 * Autoexpending Twitter Collection.
 */
class Collection extends Base
{
    /**
     * Do a fetch for all entities.
     * Implies loading all pages of this collection.
     * 
     * @param string $item
     * @param array  $params
     * @return Collection  $this
     */
    public function fetchAll($item=null, array $params=array())
    {
        if ($this->_type == 'user' && !isset($item)) return $this->fetchUsers($params);
        return parent::fetchAll($item, $params);
    }

    /**
     * Fetch all user entities.
     * We can get the info of up to 100 users per call.
     * 
     * @param array $params 
     * @return Collection  $this
     */
    private function fetchUsers(array $params=array())
    {
        $this->load();
        
        $entities = $collection->getArrayCopy();
        
        foreach ($entities as $i=>$entity) {
            if (!$entity->isStub()) continue;

            if (property_exists($entity, 'id')) {
                $ids[] = $entity->id;
                $entities[$entity->id] = $entity;

                if (count($ids) >= 100) {
                    $requests[] = (object)array('method' => 'POST', 'url' => 'users/lookup', array('user_id' => $ids) + $params);
                    $ids = array();
                }

            } else {
                $names[] = $entity->screen_name;
                $entities[$entity->screen_name] = $entity;

                if (count($names) >= 100) {
                    $requests[] = (object)array('method' => 'POST', 'url' => 'users/lookup', array('screen_name' => $names) + $params);
                    $names = array();
                }
            }
        }

        if (!empty($ids)) $requests[] = (object)array('method' => 'POST', 'url' => 'users/lookup', array('user_id' => $ids) + $params);
        if (!empty($names)) $requests[] = (object)array('method' => 'POST', 'url' => 'users/lookup', array('screen_name' => $names) + $params);
        
        $results = $this->_connection->multiRequest($requests, false);
        
        foreach ($results as $result) {
            foreach ($result as $data) {
                $key = isset($entitys[$data->id]) ? 'id' : 'screen_name';
                $entitys[$data->$key]->setProperties($data, true);
            }
        }

        return $this;
    }
}
