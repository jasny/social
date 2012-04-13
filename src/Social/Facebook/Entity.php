<?php
/**
 * Social Entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Facebook;

use Social\Entity as Base;
use Social\Exception;

/**
 * Autoexpending Facebook Entity.
 */
class Entity extends Base
{
    /**
     * Metadata.
     * 
     * @var Entity
     */
    protected $_metadata;
    
    
    /**
     * Convert value to Entity
     * 
     * @param mixed $value
     * @return mixed 
     */
    protected function convertProperty($value)
    {
        if (is_scalar($value) || is_null($var)) {
            if (preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d$/', $value)) return new \DateTime($value);
            return $value;
        }

        if ($value instanceof stdClass && isset($value->id)) return new Entity($this->_connection, $value, null, true);
        
        // TODO autoexpending array
        
        if (is_array($value) || $value instanceof stdClass) {
            foreach ($value as &$v) {
                $v = $this->convertProperty($v);
            }
        }
        
        // Probably some other kind of object
        return $value;
    }

    /**
     * Get Entity type.
     * 
     * @return string
     */
    public function getType()
    {
         if (!isset($this->_type) && isset($this->id)) $this->_type = $this->getMetadata()->type;
         return $this->_type;
    }
    
    /**
     * Get metadata.
     * 
     * return Entity
     */
    public function getMetadata()
    {
        if (isset($this->_metadata)) {
            if (!isset($this->id)) throw new Exception("Unable to fetch metadata. The id is unknown.");
            
            $data = $this->_connection->fetchData($this->id, array('fields' => 'id', 'metadata' => true));
            $this->_metadata = new self($data->metadata);
        }
        
        return $this->_metadata;
    }
    
    /**
     * Get subdata.
     * 
     * @param string $item
     * @param array  $params
     * @return mixed
     */
    public function fetch($item, array $params=array())
    {
        if (!isset($this->id)) throw new Exception("Unable to fetch subdata. The id is unknown.");
        $this->$item = $this->_connection->fetch($this->id, $params);
        
        return $this->$item;
    }
    
    /**
     * Fetch new data from Facebook.
     * 
     * @param boolean $expand  Get all properties if this is a stub
     */
    public function reload($expand=true)
    {
        if (!isset($this->id)) throw new Exception("Unable to reload. The id is unknown.");
        
        $id = $this->id;
        $fields = array();
        
        // Clear all properties
        foreach ($this as $key=>&$value) {
            if ($key[0] == '_') continue;
            unset($this->$key);
            $fields[] = $key;
        }
        
        // Fetch and set new properties
        if (!$this->_stub || $expand) $params = array('fields'=>$fields);
        
        $data = $this->_connection->fetchData($this->id, $params);
        $this->setProperties($data);
        
        $this->_stub = false;
    }
}