<?php

namespace Social;

/**
 * A result with entities as the keys
 */
class Result extends \SplObjectStorage implements Data
{
    /**
     * Social connection
     * @var Connection
     */
    protected $_connection;

    
    /**
     * Class constructor
     * 
     * @param Connection       $connection
     * @param array|Collection $entities
     */
    public function __construct(Connection $connection, $entities=array())
    {
        $this->_connection = $connection;
        
        foreach ($entities as $entity) {
            $this->attach($entity);
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
     * Get the entities
     * 
     * @return Collection
     */
    public function getEntities()
    {
        $entities = array();
        
        $this->rewind();
        while ($this->valid()) {
            $entities[] = $this->key();
        }
        
        return new Collection($this->getConnection(), $entities);
    }
    
    /**
     * Update current entities and values with newly fetched data.
     * 
     * @param array|Result $data
     * @return Result
     */
    public function setData($data)
    {
        $entities = $this->getEntities();
        
        foreach ($data as $key=>$value) {
            $entity = is_scalar($key) ? $entities[$key] : $entities->find($key);
            if (!$entity) continue; // Shouldn't happen
            
            if (!is_int($key)) $entity->setData($key);
            
            if ($this[$entity] instanceof Data) $this[$entity]->setData($value);
              else $this[$entity] = $value;
        }
        
        return $this;
    }
}
