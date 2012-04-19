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
     * Set properties.
     * 
     * @param array   $data 
     * @param boolean $stub  Set to false to indicate the entity should no long be considered a stub
     */
    public function setProperties($data, $stub=null)
    {
        foreach ($data as $key=>&$value) {
            $this->$key = $this->convertData($value);
        }
        
        if (isset($stub)) $this->_stub &= $stub;
    }
    
    /**
     * Convert value to object.
     * 
     * @param mixed $data
     * @return mixed 
     */
    protected function convertData($data)
    {
        return $this->getConnection()->convertData($data);
    }
    
    /**
     * Get API connection.
     * 
     * @return Connection
     */
    public function getConnection()
    {
        if (!isset($this->_connection)) throw new Exception('This entity is not connected to an API. Please use $object->reconnectTo($connection)');
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
     * 
     * @param array $params
     */
    public function expand(array $params=array())
    {
        if ($this->_stub) $this->reload(true, $params);
    }
    
    /**
     * Fetch new data.
     * 
     * @param array   $params
     * @param boolean $expand  Get all properties if this is a stub
     * @return Entity  $this
     */
    abstract public function reload(array $params=array(), $expand=true);
    
    
    /**
     * Fetch subdata.
     * 
     * @param string $item
     * @param array  $params
     * @return mixed
     */
    abstract public function get($item, array $params=array());
    
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
        
        return $this->get($name);
    }
    
    
    /**
     * Serialization
     * { @internal Don't serialze the connection }}
     * 
     * @return array
     */
    public function __sleep()
    {
        $props = get_object_vars($this);
        unset($props['_connection']);
        return array_keys($props);
    }
    
    /**
     * Reconnect an unserialized Entity.
     * 
     * @param Connection $connection
     * @return Entity 
     */
    public function reconnectTo(Connection $connection)
    {
        if (isset($this->_connection)) throw new Exception("Unable to reconnect Entity: I'm already connected.");
        $this->_connection = $connection;
        
        return $this;
    }
}