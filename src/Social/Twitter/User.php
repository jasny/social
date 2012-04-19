<?php
/**
 * Twitter User entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Facebook;

use Social\Entity;
use Social\Exception;

/**
 * Autoexpending Twitter User entity.
 */
class User extends Entity
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
        $this->_type = 'user';
        $this->_stub = $stub;
        
        if (is_scalar($data)) $data = array('id' => $data);
        $this->setProperties($data);
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
        switch ($item) {
            case 'timeline':               $resource = 'statuses/user_timeline'; break;
            case 'retweeted_by_user':      $resource = 'statuses/retweeted_by_user'; break;
            case 'retweeted_to_user':      $resource = 'statuses/retweeted_to_user'; break;
            case 'followers':              $resource = 'followers/ids'; break;
            case 'friends':                $resource = 'friends/ids'; break;
            case 'incomming_friendships':  $resource = 'friendships/incomming'; break;
            case 'outgoing_friendships':   $resource = 'friendships/outgoing'; break;
        }
        
        if (!isset($resource)) return parent::fetch($item, $params);
        
        $this->$item = $this->getConnection()->get($resource, $params);
        return $this->$item;
    }
    
    /**
     * Fetch new data from Facebook.
     * 
     * @param array   $params
     * @param boolean $expand  Get all properties if this is a stub
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
        
        $this->_stub = false;
    }
}