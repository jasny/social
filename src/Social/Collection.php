<?php
/**
 * Base class for Collections
 * 
 * @license MIT
 * @copyright 2012-2014 Jasny
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
     * Request object to fetch next page
     * @var object
     */
    protected $_nextPage;

    
    /**
     * Class constructor
     * 
     * @param array      $data
     * @param Connection $connection
     * @param object     $nextPage    Request object to fetch next page
     */
    public function __construct(array $data=[], $connection=null, $nextPage=null)
    {
        $this->_connection = $connection;
        
        if (isset($nextPage)) {
            $this->_nextPage = is_string($nextPage) ? (object)array('url' => $nextPage) : (object)$nextPage;
        }
        
        parent::__construct($data);
    }
    
    /**
     * Get API connection.
     * 
     * @return Connection
     */
    public function getConnection()
    {
        if (!isset($this->_connection))
            throw new Exception('This collection is not connected to an API. Please reconnect before use.');
        
        return $this->_connection;
    }
    
    /**
     * Fetch the next page.
     * 
     * @return Collection
     */
    public function nextPage()
    {
        if (!isset($this->_nextPage)) return null;
        return $this->getConnection()->doRequest($this->_nextPage);
    }
    
    
    /**
     * Expand all stubs.
     * 
     * @return Collection  $this
     */
    public function hydrate()
    {
        $this->getConnection()->prepare($this);
        foreach ($this as $entity) {
            if ($entity->isStub()) $this->getConnection()->nop();
             else $entity->refresh();
        }
        return $this->getConnection()->execute();
    }

    /**
     * Refresh all entities.
     * 
     * @return Collection  $this
     */
    public function refresh()
    {
        $this->getConnection()->prepare($this);
        foreach ($this as $entity) $entity->refresh();
        return $this->getConnection()->execute();
    }
    
    /**
     * Perform an action on all entities in the collection.
     * 
     * @param string $name
     * @param array  $arguments
     * @return Result
     */
    public function __call($name, $arguments)
    {
        $conn = $this->getConnection();
        $result = new Result($conn, $this);
        
        $conn->prepare($result);
        foreach ($this as $entity) {
            call_user_func_array(array($entity, $name), $arguments);
        }
        
        return $conn->execute();
    }
    
    
    /**
     * Serialization
     * { @internal Don't serialze the connection }}
     * 
     * @return array
     */
    public function serialize()
    {
        if (isset($this->_connection)) {
            $clone = clone $this;
            unset($clone->_connection);
            return $clone->serialize();
        }
        
        return parent::serialize();
    }
    
    /**
     * Reconnect an unserialized Entity.
     * 
     * @param Connection $connection
     * @return Collection  $this
     */
    public function reconnectTo(Connection $connection)
    {
        if (isset($this->_connection)) throw new Exception("Unable to reconnect Collection: I'm already connected.");
        $this->_connection = $connection;
        
        foreach ($this as $entity) {
            $entity->reconnectTo($connection);
        }
        
        return $this;
    }
}
