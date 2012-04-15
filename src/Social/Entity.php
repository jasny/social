<?php
/**
 * Base class for Entities
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * An autoexpanding Entity.
 */
abstract class Entity
{
    /**
     * Social connection
     * @var Connection
     */
    protected $_connection;

    /**
     * Entity type
     * @var Connection
     */
    protected $_type;
    
    /**
     * Entity is a stub
     * @var boolean
     */
    protected $_stub = false;
    
    /**
     * Class constructor
     * 
     * @param Connection $connection
     * @param string     $type
     * @param object     $data 
     * @param boolean    $stub
     */
    public function __construct(Connection $connection, $type=null, $data=array(), $stub=false)
    {
        $this->_connection = $connection;
        $this->_type = $type;
        $this->_stub = $stub;
        
        $this->setProperties($data);
    }

    /**
     * Set properties
     * 
     * @param Entity $data 
     */
    protected function setProperties($data)
    {
        foreach ($data as $key=>&$value) {
            $this->$key = $this->convertData($value);
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
     * Get Entity type.
     * 
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }
    
    /**
     * Check if this Entity is a stub.
     * 
     * @return boolean 
     */
    public function isStub()
    {
        return $this->_stub;
    }
    
    /**
     * Expand stub by fetching new data.
     */
    public function expand()
    {
        if ($this->_stub) $this->reload(true);
    }
    
    /**
     * Fetch new data.
     * 
     * @param boolean $expand  Get all properties if this is a stub
     */
    abstract public function reload($expand=true);
    
    /**
     * Get subdata.
     * 
     * @param string $item
     * @param array  $params
     * @return mixed
     */
    abstract public function fetch($item, array $params=array());
    
    /**
     * Expand a stub when trying to get a non existing property.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->_stub) {
            $this->expand();
            if (property_exists($this, $name)) return $this->$name;
        }
        
        return $this->fetch($name);
    }
}