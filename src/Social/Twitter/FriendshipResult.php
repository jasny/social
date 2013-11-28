<?php

namespace Social\Twitter;

/**
 * A result with entities as the keys
 */
class FriendshipResult extends \Social\Result
{
    protected $key;
    
    /**
     * Class constructor
     * 
     * @param Connection       $connection
     * @param array|Collection $users
     * @param string           $key
     */
    public function __construct(Connection $connection, $users, $key=null)
    {
        $this->key = $key;
        parent::__construct($connection, $users);
    }

    /**
     * Convert the result of friendships/lookup.
     * 
     * @param Entity  $data
     * @param int     $i
     * @return array
     */
    protected function processResult($data, $i)
    {
        $friendship = (object)array(
            'following' => in_array('following', $data->connections),
            'following_requested' => in_array('following_requested', $data->connections),
            'followed_by' => in_array('followed_by', $data->connections)
        );
        unset($data->connections);

        $value = $key ? $friendship->{$this->key} : $friendship;
        
        parent::processResult($value, $i);
        if ($this->key()->isStub()) $this->key()->processResult($value, $i);
    }
}
