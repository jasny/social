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
class Collection extends \ArrayObject implements Data
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
     * @param Connection $connection
     * @param array      $data
     * @param object     $nextPage    Request object to fetch next page
     */
    public function __construct(Connection $connection, array $data=array(), $nextPage=null)
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
     * Perform an action on all entities in the collection.
     * 
     * @param string $name
     * @param array  $arguments
     * @return Result
     */
    public function __call($name, $arguments)
    {
        $conn = $this->getConnection();
        
        // Prepare and execute
        $conn->prepare($this);
        foreach ($this as $entity) {
            call_user_func_array(array($entity, $name), $arguments);
        }
        $data = $conn->execute();
        
        if ($name == 'expand') return $data;
    }
    
    /**
     * Set properties of all entities (or create result).
     * 
     * @param array   $data
     * @param boolean $expanded  Entity is no longer a stub
     * @return Result|Collection
     */
    public function setData($data, $expanded=false)
    {
        $result = null;
        $keys = array_keys($this->getArrayCopy());        
        
        foreach ($data as $i=>$item) {
            $key = $keys[$i];
            
            if ($this[$key]->is($item)) {
                $this[$key]->setData($item, $expanded);
            } else {
                if (!isset($result)) $result = new Result($this->getConnection());
                $result[$this[$key]] = $item;
            }
        }
        
        return $result ?: $this;
    }
}
