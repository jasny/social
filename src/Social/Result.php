<?php

namespace Social;

/**
 * A result with entities as the keys
 */
class Result extends \SplObjectStorage
{
    /**
     * Social connection
     * @var Connection
     */
    protected $_connection;

    
    /**
     * Class constructor
     * 
     * @param Connection $connection
     * @param array      $data
     */
    public function __construct(Connection $connection)
    {
        $this->_connection = $connection;
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
        foreach ($this as $entity=>$value) $entities[] = $entity;
        
        return new Collection($this->getConnection(), $entities);
    }
}
