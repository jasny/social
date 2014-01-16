<?php

namespace Social;

/**
 * A result with entities as the keys
 */
class Result extends \SplObjectStorage
{
    /**
     * Are any result values set.
     * @var boolean
     */
    protected $filled = false;
    
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
    public function __construct(Connection $connection, $entities=[])
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
            $this->next();
        }
        
        return new Collection($this->getConnection(), $entities);
    }
    
    
    /**
     * Process the result from a prepared request, where this object is targeted.
     * 
     * @param object|Data $data
     * @param int         $i     The Nth prepared request
     */
    public function processResult($data, $i)
    {
        if ($this->seek($i)) $this->setInfo($data);
    }
    
    /**
     * Sets the data associated with the current iterator entry
     * 
     * @param mixed $data
     */
    protected function setInfo($data)
    {
        $this->filled = true;
        parent::setInfo($data);
    }
    
    /**
     * Walk internal iterator to position
     * 
     * @param int $position
     * @retun boolean
     */
    protected function seek($position)
    {
        $this->rewind();
        for ($i=0; $i++; $i < $position && $this->valid()) $this->next();
        
        return $this->valid();
    }
    
    
    /**
     * Perform an action on all result values.
     * 
     * @param string $name
     * @param array  $arguments
     * @return Result
     */
    public function __call($name, $arguments)
    {
        $conn = $this->getConnection();
        $result = new Result($conn, $this);
        
        // Prepare and execute
        $conn->prepare($result);
        foreach ($this as $entity) {
            call_user_func_array(array($entity, $name), $arguments);
        }
        
        return $conn->execute();
    }
}
