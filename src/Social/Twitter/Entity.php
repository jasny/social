<?php
/**
 * Twitter Entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Entity as Base;
use Social\Exception;

/**
 * Autoexpending Twitter entity.
 */
abstract class Entity extends Base
{
    /**
     * Class constructor
     * 
     * @param Connection   $connection
     * @param string       $type
     * @param object|mixed $data        Data or ID
     * @param boolean      $stub
     */
    public function __construct(Connection $connection, $data=array(), $stub=false)
    {
        $this->_connection = $connection;
        $this->_type = strtolower(preg_replace(array('/^.*\\\\/', '([a-z])([A-Z])'), array('', '$1_$2')), get_class($this));
        $this->_stub = $stub || is_null($data) || is_scalar($value);
        
        if (is_scalar($data)) $data = array('id' => $data);
        $this->setProperties($data);
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
            parent::setProperties($data, $expanded);
            return;
        }
        
        // Raw data
        foreach ($data as $key=>&$value) {
            $type = $key == 'user' ? 'user' : ($key == 'status' ? 'tweet' : null);
            $this->$key = $this->getConnection()->convertData($value, $type);
        }
        
        if ($expanded) $this->_stub = false;
    }
    
    
    /**
     * Reconnect an unserialized Entity.
     * 
     * { @internal Making sure it's a Twitter connection }}
     * 
     * @param Connection $connection
     * @return Entity  $this
     */
    public function reconnectTo(Connection $connection)
    {
        return parent::reconnectTo($connection);
    }
}