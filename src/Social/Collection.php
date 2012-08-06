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
 * A collection of entities.
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
     * Fetch the next page.
     * 
     * @return Collection
     */
    public function fetchNextPage()
    {
        if (!isset($this->_nextPage)) return null;
        return $this->getConnection()->doRequest($this->_nextPage);
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
        trigger_error('Social\Collections are read only', E_USER_ERROR);
    }
    
    /**
     * Add/change by offset.
     * 
     * @param int|null $offset The offset to assign the value to.
     * @param Entity|mixed $value  The value to set.
     */
    public function offsetSet($offset, $value)
    {
        trigger_error('Social\Collections are read only', E_USER_ERROR);
    }

    /**
     * Unset by offset.
     * 
     * @param int $offset  The offset to unset.
     */
    public function offsetUnset($offset)
    {
        trigger_error('Social\Collections are read only', E_USER_ERROR);
    }
}
