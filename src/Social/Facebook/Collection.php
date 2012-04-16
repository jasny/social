<?php
/**
 * Social Entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Facebook;

use Social\Collection as Base;
use Social\Exception;

/**
 * An autoexpanding facebook collection.
 */
class Collection extends Base
{
    /**
     * Load next page.
     * 
     * @return boolean  True if any new data has been added
     */
    public function loadNext()
    {
        if (!isset($this->_nextPage)) return false;
        
        $collection = $this->_connection->fetch($this->_nextPage);
        if (!$collection instanceof self) throw new Exception("I expected a Collection, but instead got " . (is_object($collection) ? 'a ' . get_class($collection) : (is_scalar($collection) ? "'$collection'" : 'a ' . get_type($collection))));

        if ($collection->count() == 0) {
            if (!empty($collection->_nextPage) && $this->_nextPage != $collection->_nextPage) {
                $this->_nextPage = $collection->_nextPage;
                return $this->loadNext();
            }
            
            $this->_nextPage = null;
            return false;
        }

        $this->_nextPage = !empty($collection->_nextPage) && $this->_nextPage != $collection->_nextPage ? $collection->_nextPage : null;
        $this->appendData($collection->getArrayCopy());
        
        return true;
    }
    
    /**
     * Search data for item.
     * 
     * @param mixed $item  Value or id
     * @param array $data
     * @return int  The key
     */
    protected function search($item, &$data=null)
    {
        if (!isset($data)) $data = $this->getArrayCopy();
        if ($item instanceof Entity) $item = $item->id;
        
        $this->loadAll();
        foreach ($data as $key => &$value) {
            if (($value instanceof Entity ? $value->id : $value) == $item) return $key;
        }
    }
}