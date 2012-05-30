<?php
/**
 * Base class for Collections
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * An autoexpanding collection.
 */
class Collection extends \ArrayObject
{
    /**
     * Social connection
     * @var Connection
     */
    protected $_connection;
    
    /**
     * Type of entities in the collection
     * @var string
     */
    protected $_type;
    
    /**
     * Request object to fetch next page
     * @var object
     */
    protected $_nextPage;
    
    /**
     * Load more items if available
     * @var boolean
     */
    protected $_autoload = true;
    
    /**
     * Added items
     * @var array
     */
    protected $_added = array();
    
    /**
     * Removed items
     * @var array
     */
    protected $_removed = array();

    
    /**
     * Class constructor
     * 
     * @param Connection $connection
     * @param string     $type
     * @param array      $data
     * @param object     $nextPage    Request object to fetch next page
     */
    public function __construct(Connection $connection, $type=null, array $data=array(), $nextPage=null)
    {
        $this->_connection = $connection;
        $this->_type = $type;
        
        if (isset($nextPage)) {
            $this->_nextPage = is_string($nextPage) ? (object)array('url' => $nextPage) : (object)$nextPage;
            if (isset($this->_nextPage->count) && $this->_nextPage->count == 0) {
                unset($this->_nextPage->count);
                $this->_autoload = false;
            }
        }
        
        foreach ($data as &$entry){
            $entry = $connection->convertData($entry, $type);
        }
        
        parent::__construct($data);
    }
    
    /**
     * Get iterator used for foreach loop.
     * 
     * @return CollectionIterator 
     */
    public function getIterator()
    {
        return new CollectionIterator($this);
    }

    /**
     * Append new data to the collection.
     * 
     * @param array $data
     */
    protected function appendData(array $data)
    {
        foreach ($data as &$value) {
            parent::append($value);
        }
    }
    
    
    /**
     * Get API connection.
     * 
     * @return Connection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Get the type of items in the collection.
     * 
     * @return string 
     */
    public function getType()
    {
        return $this->_type;
    }
    
    
    /**
     * Load next page.
     * 
     * @param int $i  Prevents invinite loop
     * @return boolean  True if any new data has been added
     */
    protected function loadNextPage($i=0)
    {
        if (!isset($this->_nextPage)) return false;
        
        $result = $this->getConnection()->multiRequest(array($this->_nextPage));
        if (empty($result)) return false; // HTTP request failed has failed. To bad, I won't complain
        
        $collection = reset($result);
        if (!$collection instanceof self) throw new Exception("I expected a Collection, but instead got " . (is_object($collection) ? 'a ' . get_class($collection) : (is_scalar($collection) ? "'$collection'" : 'a ' . get_type($collection))));

        $next_is_same = !empty($collection->_nextPage) && $this->getConnection()->getUrl($this->_nextPage) != $this->getConnection()->getUrl($collection->_nextPage);
        
        if ($collection->count() == 0) {
            // Hmm got nothing, perhaps if I try again, but only once
            if (!$next_is_same && $i < 1) {
                $this->_nextPage = $collection->_nextPage;
                return $this->loadNextPage($count, $i + 1);
            }
            
            $this->_nextPage = null;
            return false;
        }

        $this->_nextPage = $next_is_same ? $collection->_nextPage : null;
        
        $data = $collection->getArrayCopy();
        if (isset($count) && count($data) > $count) $data = array_splice($data, 0, $count);

        $this->appendData($data);
        return true;
    }
    
    /**
     * Load items by fetching next pages.
     * 
     * @param int $count  The number of items you want in the collection.
     * @return Collection  $this
     */
    public function load($count=null)
    {
        if (!isset($this->_nextPage) || (!isset($count) && !$this->_autoload)) return $this;
        
        while (!isset($count) || $this->count(false) < $count) {
            if (!$this->loadNextPage(isset($count) ? $this->count(false) - $count : null)) break;
        }
        
        $this->_autoload = false;
        return $this;
    }

    /**
     * Whether all items are loaded.
     * 
     * @param boolean $available  Ignore count and return if a next page can be loaded.
     * @return boolean
     */
    public function isLoaded($available=false)
    {
        return $this->_nextPage && ($available || $this->_autoload);
    }
    
    
    /**
     * Do a fetch for all entities.
     * Implies loading all pages of this collection.
     * 
     * @param string $item
     * @param array  $params
     * @param int    $maxPages   Maximum number of pages to load when fetching collections
     * @return Collection  $this
     */
    public function fetchAll($item=null, array $params=array(), $maxPages=5)
    {
        $this->load();
        
        $requests = array();
        $entities = $this->getArrayCopy();
        
        foreach ($entities as $i=>$entity) {
            if (!$entity instanceof Entity) continue;
            
            $request = $entity->prepareRequest($item, $params);
            if (isset($request) && (!isset($request->method) || $request->method == 'GET')) $requests[$i] = $request;
        }

        if (empty($requests)) throw new Exception("It's not possible to fetch $item for the entities in this collection.");
        
        $new_requests = array();
        $results = $this->getConnection()->multiRequest($requests);

        foreach ($results as $i=>$result) {
            if (!isset($item)) {
                if ($entities[$i] instanceof Entity) $entities[$i]->setProperties($result);
                  else $entities[$i] = $result;
            } else {
                if (isset($entities[$i]->$item) && $entities[$i] instanceof Entity) $entities[$i]->$item->setProperties($result);
                  else $entities[$i]->$item = $result;
            }

            if ($result instanceof self && !$result->isLoaded()) $new_requests[$i] = $result->_nextPage;
        }
        
        while (!empty($new_requests) && --$maxPages > 0) {
            $requests = $new_requests;
            $new_requests = array();
            $results = $this->getConnection()->multiRequest($requests);
            
            foreach ($results as $i=>$result) {
                if (!$result instanceof Collection) continue; // Unexpected behaviour
                
                if (!isset($item)) $entities[$i]->appendData($result);
                  else $entities[$i]->$item->appendData($result);
                
                if ($result instanceof self && !$result->isLoaded()) $new_requests[$i] = $result->_nextPage;
            }
        }
        
        return $this;
    }
    
    /**
     * Perform an action on all entities in the collection.
     * Implies loading all pages of this collection.
     * 
     * @param string $action
     * @param mixed  $target  Entity/id or array with entities/ids
     * @param array  $params
     */
    public function forAll($action, $target=null, array $params=array())
    {
        $this->load();
        
        $requests = array();
        $entities = $this->getArrayCopy();
        
        foreach ($entities as $i=>$entity) {
            if (!$entity instanceof Entity) continue;
            
            $request = $entity->prepareRequest($item, $params);
            if (isset($request) || !isset($request->method) || $request->method != 'GET') $requests[$i] = $request;
        }

        if (empty($requests)) throw new Exception("It's not possible to perform $action for the entities in this collection.");
        
        $new_requests = array();
        return $this->getConnection()->multiRequest($requests);
    }
    
    
    /**
     * Get added items.
     * 
     * @return array
     */
    public function getAdded()
    {
        return $this->_added;
    }
    
    /**
     * Get removed items.
     * 
     * @return array
     */
    public function getRemoved()
    {
        return $this->_removed;
    }
    
    /**
     * Check if there are added or removed items.
     * 
     * @return boolean
     */
    public function hasChanged()
    {
        return !empty($this->_added) || !empty($this->_removed);
    }
    
    
    /**
     * Search data for item.
     * 
     * @param mixed $entity  Value or id
     * @param array $data
     * @return int  The key
     */
    protected function search($entity, &$data=null)
    {
        if (!isset($data)) $data = $this->getArrayCopy();
        if ($entity instanceof Entity) $entity = $entity->id;
        
        $this->load();
        foreach ($data as $key => &$value) {
            if (($value instanceof Entity ? $value->id : $value) == $entity) return $key;
        }
    }
    
    /**
     * Search for item.
     * 
     * @param mixed $entity  Value or id
     * @return boolean
     */
    public function has($entity)
    {
        return $this->search($entity) !== null;
    }

    /**
     * Retrieve item.
     * 
     * @param mixed $entity  Value or id
     * @return Entity|mixed
     */
    public function get($entity)
    {
        $key = $this->search($entity);
        return isset($key) ? $this::offsetGet($key) : null;
    }

    /**
     * Add item.
     * 
     * @param Entity|mixed $entity
     */
    public function append($entity)
    {
        if (isset($this->_type) && !$entity instanceof Entity) $entity = $this->getConnection()->stub($this->_type, $entity);
        parent::append($entity);
        $this->_added[] = $entity;
    }
    
    /**
     * Remove item.
     * 
     * @param mixed $entity  Value or id
     * @return Entity|mixed  Removed item
     */
    public function remove($entity)
    {
        $key = $this->search($entity, $this->_data);
        if (!isset($key)) return null;
        
        $entity =& $this->_data[$key];
        unset($this->_data[$key]);
        $this->_removed[$key] = $entity;
        
        return $entity;
    }
    
    
    /**
     * Whether a offset exists.
     * 
     * @param int $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $this->load();
        return parent::offsetExists($offset);
    }

    /**
     * Retrieve by offset.
     * 
     * @param int $offset  The offset to retrieve.
     * @return Entity|mixed
     */
    public function offsetGet($offset)
    {
        $this->load();
        return parent::offsetGet($offset);
    }

    /**
     * Add/change by offset.
     * 
     * @param int|null $offset The offset to assign the value to.
     * @param Entity|mixed $value  The value to set.
     */
    public function offsetSet($offset, $value)
    {
        if (isset($offset) && parent::offsetExists($offset)) $this->_removed[] = parent::offsetGet($offset);
        $this->_added[] = $value;
        parent::offsetSet($offset, $value);
    }

    /**
     * Unset by offset.
     * 
     * @param int $offset  The offset to unset.
     */
    public function offsetUnset($offset)
    {
        $entity = parent::offsetGet($offset);
        
        if ($entity) {
            $this->_removed[] = $entity;
            $key = $this->search($entity, $this->_added);
            if ($key !== null) unset($this->_added[$key]);
        }
        
        parent::offsetUnset($offset);
    }
    
    
    /**
     * Count the number or items.
     * 
     * @param boolean $load  Load all items
     * @return int
     */
    public function count($load=true)
    {
        if ($load) $this->load();
        return parent::count();
    }    
}
