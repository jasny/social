<?php
/**
 * Facebook Collection
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Facebook;

use Social\Collection as Base;
use Social\Exception;

/**
 * Autoexpending Facebook Collection.
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
        foreach ($collection->getArrayCopy() as $item) {
            if (!$item instanceof Entity || !$item->isStub() || !isset($item->id)) continue;
            
            $entities[$item->id] = $item;
            $requests[] = (object)array('method' => 'GET', 'url' => $item->id, 'params' => $params);
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
}
