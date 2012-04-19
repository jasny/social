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
use Social\Entity;
use Social\Exception;

/**
 * Autoexpending Twitter Collection.
 */
class Collection extends Base
{
    /**
     * Expand all entities that are a stub.
     * 
     * Expanding all stubs at once is way faster than letting each stub autoexpand.
     * 
     * @param array $params
     * @return Collection  $this
     */
    public function expandAll(array $params = array())
    {
        $resource = null;
        $param = 'id';
        
        switch ($this->_type) {
            case 'user': return $this->expandUsers($params);
            case 'status': $resource = 'statuses/show'; break;
            case 'direct_message': $resource = 'direct_messages/show'; break;
            case 'list': $resource = 'lists/show'; break;
            case 'saved_searches': $resource = 'saved_searches/show'; break;
            case 'place': $resource = 'geo/id'; $param = 'place_id'; break;
            default: return parent::expandAll($params);
        }

        foreach ($collection->getArrayCopy() as $item) {
            if (!$item instanceof Entity || !$item->isStub() || !isset($item->id)) continue;
            
            $entities[$item->id] = $item;
            $requests[] = (object)array('method' => 'GET', 'url' => $resource, 'params' => array($param => $item->id) + $params);
        }
        
        if (empty($requests)) return $this; // Nothing to do
        
        $results[] = $this->_connection->multiRequest($requests, false);
        
        foreach ($results as $data) {
            if ($data instanceof Exception) {
                $exceptions[] = $data;
                continue;
            }
            
            if (isset($entities[$data->id])) $entities[$data->id]->setProperties($data, false);
        }

        // We ignore if only some requests failed
        if (count($results) == count($exceptions)) throw new Exception("Failed to expand the entities.", null, $exceptions);
        
        return $this;
    }

    /**
     * Expand all user entities that are a stub.
     * 
     * We can get the info of up to 100 users per call.
     * 
     * @param array $params 
     * @return Collection  $this
     */
    private function expandUsers(array $params = array())
    {
        foreach ($collection->getArrayCopy() as $user) {
            if (!$user->isStub()) continue;

            if ($user->has('id')) {
                $ids[] = $user->id;
                $entities[$user->id] = $user;

                if (count($ids) >= 100) {
                    $requests[] = (object)array('method' => 'POST', 'url' => 'users/lookup', array('user_id' => $ids) + $params);
                    $ids = array();
                }

            } else {
                $names[] = $user->screen_name;
                $entities[$user->screen_name] = $user;

                if (count($names) >= 100) {
                    $requests[] = (object)array('method' => 'POST', 'url' => 'users/lookup', array('screen_name' => $names) + $params);
                    $names = array();
                }
            }
        }

        if (!empty($ids)) $requests[] = (object)array('method' => 'POST', 'url' => 'users/lookup', array('user_id' => $ids) + $params);
        if (!empty($names)) $requests[] = (object)array('method' => 'POST', 'url' => 'users/lookup', array('screen_name' => $names) + $params);
        
        $results[] = $this->_connection->multiRequest($requests, false);
        
        foreach ($results as $data) {
            if ($data instanceof Exception) {
                $exceptions[] = $data;
                continue;
            }
            
            foreach ($data as $props) {
                $key = isset($users[$props->id]) ? 'id' : 'screen_name';
                $users[$props->$key]->setProperties($props, false);
            }
        }

        // We ignore if only some requests failed
        if (count($results) == count($exceptions)) throw new Exception("Failed to expand the entities.", null, $exceptions);
        
        return $this;
    }
}
