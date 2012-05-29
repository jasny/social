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
     * Get API connection.
     * 
     * @return Connection
     */
    public function getConnection()
    {
        if (!isset($this->_connection)) throw new Exception('This entity is not connected to an API. Please use $entity->reconnectTo($connection)');
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
     * Set properties.
     * 
     * @param array   $data 
     * @param boolean $expanded  Entity is no longer a stub
     */
    public function setProperties($data, $expanded=false)
    {
        // Data is already converted
        if ($data instanceof self) {
            foreach ($data as $key=>$value) {
                $this->$key = $value;
            }
            
            if (!$data->_stub) $this->_stub = false;
            return;
        }
        
        // Raw data
        foreach ($data as $key=>&$value) {
            $this->$key = $this->getConnection()->convertData($value);
        }
        
        if ($expanded) $this->_stub = false;
    }
    
    /**
     * Get properties (for POST).
     * 
     * @param array  $fields
     * @param string $prefix  Prefix in entity properties
     * @return array
     */
    protected function getProperties($fields=null, $prefix=null)
    {
        if (isset($prefix)) {
            $values = (array)$this;
            if (isset($fields)) $values = array_intersect_key($values, array_fill_keys($fields, null));
        
            $data = array();
            foreach ($values as $key=>$value) {
                $key = substr($key, strlen($prefix) + 1);
                $data[$key] = $value;
            }
        } else {            
            $data = array_intersect_key((array)$this, array_fill_keys($fields, null));
        }
        
        return $data;
    }
    
    
    /**
     * Get resource object for fetching subdata.
     * Preparation for a multi request.
     * 
     * @param string $action  Action or fetch item
     * @param mixed  $target  Entity/id
     * @param array  $params
     * @return object
     */
    abstract public function prepareRequest($action, $target=null, array $params=array());
    
    /**
     * Fetch new data from web service.
     * Expands the entity if it's a stub.
     * 
     * @param string $item    Sub item or null to refresh entity
     * @param array  $params
     * @return Collection|mixed
     */
    public function fetch($item=null, array $params=array())
    {
        $request = $this->prepareRequest($item, $params);

        if (!isset($request)) throw new Exception("It's not possible to fetch $item for a " . $this->getType() . ".");
        if (isset($request->method) && $request->method != 'GET') throw new Exception("Can't fetch $item for a " . $this->getType() . ": that's a {$request->method} request");
        
        $data = $this->getConnection()->get($request->resource, isset($request->params) ? $request->params : array(), false);
        
        if (!isset($item)) {
            $this->setProperties($data, true);
            return $this;
        }
        
        if ($data instanceof Collection) $data->load();
        $this->$item = $data;
        
        return $this->$item;
    }
    
    /**
     * Expand a stub when trying to get a non existing property.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        // Do we need to expand or get subdata
        $request = $this->prepareRequest($name);
        
        if (isset($request)) {
            // Prefer lazy load
            if (!empty($request->lazy)) {
                $type = $this->getConnection()->detectType($request->url);
                $this->$name = Collection($this->getConnection(), $type, array(), $request);
                
                return $this->$name;
            }
            
            return $this->fetch($name);
        }

        // Let's expand
        if ($this->isStub()) $this->fetch();
        return $this->$name;
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
     * @return Entity  $this
     */
    public function reconnectTo(Connection $connection)
    {
        if (isset($this->_connection)) throw new Exception("Unable to reconnect Entity: I'm already connected.");
        $this->_connection = $connection;
        
        return $this;
    }
}