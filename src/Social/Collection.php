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
     * Next page
     * @var string
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
     * @param object     $nextPage    Next page
     */
    public function __construct(Connection $connection, $type=null, array $data=array(), $nextPage=null)
    {
        $this->_connection = $connection;
        $this->_type = $type;
        $this->_nextPage = $nextPage;
        
        $data = $this->convertData(array_values($data));
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
    public function appendData(array $data)
    {
        $data = $this->convertData(array_values($data));
        foreach ($data as &$value) {
            $this->append($value);
        }
    }
    
    /**
     * Convert value to object.
     * 
     * @param mixed $data
     * @return mixed 
     */
    protected function convertData($data)
    {
        return $this->_connection->convertData($data, $this->_type);
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
     * @param int $count  The (max) number of items you want to load.
     * @return boolean  True if any new data has been added
     */
    protected function loadNextPage($count=null)
    {
        if (!isset($this->_nextPage) || (isset($count) && $count == 0)) return false;
        
        $collection = $this->_connection->get($this->_nextPage);
        if (!$collection instanceof self) throw new Exception("I expected a Collection, but instead got " . (is_object($collection) ? 'a ' . get_class($collection) : (is_scalar($collection) ? "'$collection'" : 'a ' . get_type($collection))));

        if ($collection->count() == 0) {
            if (!empty($collection->_nextPage) && $this->_nextPage != $collection->_nextPage) {
                $this->_nextPage = $collection->_nextPage;
                return $this->loadNextPage();
            }
            
            $this->_nextPage = null;
            return false;
        }

        $this->_nextPage = !empty($collection->_nextPage) && $this->_nextPage != $collection->_nextPage ? $collection->_nextPage : null;
        
        $data = $collection->getArrayCopy();
        if (isset($count) && $collection->count(false) > $count) $data = array_splice($data, 0, $count);
        $this->appendData($this);
        
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
        while (!isset($count) || $this->count(false) < $count) {
            if (!$this->loadNextPage(isset($count) ? $this->count(false) - $count : null)) break;
        }
        
        $this->_autoload = false;
        return $this;
    }

    /**
     * Whether all items are loaded.
     * 
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->_nextPage && $this->_autoload;
    }
    
    
    /**
     * Expand all stubs.
     * 
     * { @internal Please overwrite this and do a multi request }}
     * 
     * @param array $params
     * @return Collection  $this
     */
    public function expandAll(array $params = array())
    {
        foreach ($collection->getArrayCopy() as $item) {
            if ($item instanceof Entity) $item->expand($params);
        }
        
        return $this;
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
    
    /**
     * Search for item.
     * 
     * @param mixed $item  Value or id
     * @return boolean
     */
    public function has($item)
    {
        return $this->search($item) !== null;
    }

    /**
     * Retrieve item.
     * 
     * @param mixed $item  Value or id
     * @return Entity|mixed
     */
    public function get($item)
    {
        $key = $this->search($item);
        return isset($key) ? $this::offsetGet($key) : null;
    }

    /**
     * Add item.
     * 
     * @param Entity|mixed $item
     */
    public function append($item)
    {
        if (isset($this->_type) && !$item instanceof Entity) $item = $this->_connection->stub($this->_type, $item);
        parent::append($item);
        $this->_added[] = $item;
    }
    
    /**
     * Remove item.
     * 
     * @param mixed $item  Value or id
     * @return Entity|mixed  Removed item
     */
    public function remove($item)
    {
        $key = $this->search($item, $this->_data);
        if (!isset($key)) return null;
        
        $item =& $this->_data[$key];
        unset($this->_data[$key]);
        $this->_removed[$key] = $item;
        
        return $item;
    }
    
    
    /**
     * Whether a offset exists.
     * 
     * @param int $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $this->loadAll();
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
        $this->loadAll();
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
        $item = parent::offsetGet($offset);
        
        if ($item) {
            $this->_removed[] = $item;
            $key = $this->search($item, $this->_added);
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
