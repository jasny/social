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
abstract class Collection extends \ArrayObject
{
    /**
     * Social connection
     * @var Connection
     */
    protected $_connection;
    
    /**
     * Next page
     * @var object
     */
    protected $_nextPage;
    
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
     * @param object     $next        Next page
     */
    public function __construct(Connection $connection, array $data=array(), $next=null)
    {
        $this->setIteratorClass(__NAMESPACE__ . '\CollectionIterator');
        
        $this->_connection = $connection;
        $this->_paging = $paging;
        
        $data = $this->convertData(array_values($data));
        parent::__construct($data);
    }
    
    /**
     * Append new data to the collection.
     * 
     * @param array $data 
     */
    protected function appendData(array $data)
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
        return $this->_connection->convertData($data);
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
     * Load next page.
     * 
     * @return boolean  True if any new data has been added
     */
    abstract public function loadNext();
    
    /**
     * Load all items by getting next and previous pages.
     * 
     * @return boolean  True if any new data has been added
     */
    public function loadAll()
    {
        if (!isset($this->_nextPage)) return false;
        
        $ret = false;
        while ($this->loadNext()) $ret = true;
        return $ret;
    }

    /**
     * Whether all items are loaded.
     * 
     * @return boolean
     */
    public function isLoaded()
    {
        return isset($this->_nextPage);
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
    abstract protected function search($item, &$data=null);
    
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
        parent::append($item);
        
        
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
	abstract public function offsetUnset($offset)
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
	 * @return int
	 */
	public function count()
    {
        $this->loadAll();
        return parent::count();
    }    
}
