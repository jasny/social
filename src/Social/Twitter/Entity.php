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
     * @param object|mixed $data         Data or ID
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
     * Get resource object for fetching subdata.
     * Preparation for a multi request.
     * 
     * @param string $item
     * @param array  $params
     * @return object
     */
    public function prepareRequest($item, array $params=array())
    {
        throw new Exception("It's not possible to " . (isset($item) ? "fetch $item for" : "refresh") . " a " . $this->getType() . ".");
    }
    
    /**
     * Get subdata from Twitter.
     * 
     * @param string $item
     * @param array  $params
     * @return Collection|mixed
     */
    public function fetch($item, array $params=array())
    {
        $request = $this->prepareRequest($item, $params);

        if (!isset($request)) throw new Exception("It's not possible to fetch $item for a " . $this->getType() . ".");
        if (isset($request->method) && $request->method != 'GET') throw new Exception("Can't fetch $item for a " . $this->getType() . ": that's a {$request->method} request");
        
        if (!isset($request->load) || $request->load) {
            $this->$item = $this->getConnection()->get($request->resource, $request->params);
        } else {
            $this->$item = $this->getConnection()->collection(null, array(), $request);
        }
            
        return $this->$item;
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