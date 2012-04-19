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
            
            $data = $this->_connection->getData($this->id, array('fields' => 'id', 'metadata' => true));
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
    public function get($item, array $params=array())
    {
        if (!isset($this->id)) throw new Exception("Unable to fetch subdata. The id is unknown.");
        $this->$item = $this->_connection->get("{$this->id}/$item", $params);
        
        return $this->$item;
    }
    
    /**
     * Fetch new data from Facebook.
     * 
     * @param array   $params
     * @param boolean $expand  Get all properties if this is a stub
     * @return Entity  $this
     */
    public function reload(array $params=array(), $expand=true)
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
        if ((!$this->_stub || !$expand) && !isset($params['fields'])) $params['fields'] = $fields;
        
        $data = $this->_connection->getData($id, $params);
        
        $this->setProperties($data);
        if ($expand) $this->_stub = false;
        
        return $this;
    }
}