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
    public function __construct(Connection $connection, $data=[], $stub=self::NO_STUB)
    {
        if (!$stub && (is_null($data) || is_scalar($data))) $stub = self::STUB;
        if (is_scalar($data)) $data = array('id' => $data);
        
        parent::__construct($connection, $data, $stub);
    }
}
