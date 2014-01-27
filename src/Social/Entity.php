<?php
/**
 * Jasny Social
 * World's best PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social;

/**
 * Base class for entities.
 */
abstract class Entity
{
    /** No stub */
    const NO_STUB = 0;
    
    /** Doesn't contain all of entities properties */
    const STUB = 1;

    /** Auto-hydrating stub */
    const AUTO_HYDRATE = 2;
    
    
    /**
     * Social connection
     * @var Connection
     */
    protected $_connection;
    
    /**
     * Entity is a stub
     * @var int
     */
    protected $_stub = self::STUB;

    /**
     * Class constructor
     * 
     * @param object     $data
     * @param int        $stub        Entity::NO_STUB, Entity::STUB or Entity::AUTO_HYDRATE
     * @param Connection $connection
     */
    public function __construct($data=[], $stub=self::STUB, $connection=null)
    {
        $this->_connection = $connection;
        $this->_stub = $stub;

        foreach ($data as $key=>&$value) {
            $this->$key = $value;
        }
        $this->cast();
    }
    
    /**
     * Cast some of the data to entities
     */
    protected function cast()
    { }
    

    /**
     * Get API connection.
     * 
     * @return Connection
     */
    public function getConnection()
    {
        if (!isset($this->_connection))
            throw new Exception('This entity is not connected to an API. Please reconnect before use.');
        
        return $this->_connection;
    }
    

    /**
     * Get the unique identifier of the entity.
     * 
     * @return mixed
     */
    abstract public function getId();
    
    /**
     * Check if entity is the same as the provided entity or id.
     * 
     * @param Entity|string $entity
     * @return boolean
     */
    public function is($entity)
    {
        return is_object($entity) ?
            get_class($this) == get_class($entity) && $this->getId() == $entity->getId() :
            $this->getId() == $entity;
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
     * If this entity is a stub, fetch to get all properties.
     * 
     * @return Entity $this
     */
    public function hydrate()
    {
         if ($this->isStub()) $this->refresh();
         return $this;
    }
    
    /**
     * Fetch data of this entity.
     * 
     * @return Entity $this
     */
    abstract public function refresh();

    /**
     * Process the result from a prepared request, where this object is targeted.
     * 
     * @param Entity $data
     * @param int    $i     (ignored)
     */
    public function processResult($data, $i)
    {
        if ($data instanceof self || !$this->is($data))
            throw new Exception("Unable to set data: Result doesn't represent this entity.");
        
        if (!$data->isStub()) $this->_stub = self::NO_STUB;
        
        foreach ($data as $key=>&$value) {
            $this->$key = $value;
        }
    }

    
    /**
     * Expand a stub when trying to get a non existing property.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->_stub === self::AUTO_HYDRATE) {
            $this->hydrate();
        } elseif ($this->_stub) {
            $class = preg_replace('/^.+\\\\/', '', get_class($this));
            trigger_error("This $class is a stub, please hydrade to get all properties.", E_USER_NOTICE);
        }
        
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
