<?php
/**
 * Facebook Entity
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
        $params = !$this->_stub || !$expand ? array('fields'=>$fields) : array();
        
        $data = $this->_connection->fetchData($id, $params);
        $this->setProperties($data);
        
        $this->_stub = false;
    }
}