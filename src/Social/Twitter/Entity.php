<?php
/**
 * Twitter Entity
 * 
 * @license MIT
 * @copyright 2012-2014 Jasny
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
     * @param object|mixed $data        Data or ID
     * @param boolean      $stub
     * @param Connection   $connection
     */
    public function __construct($data=[], $stub=self::STUB, $connection=null)
    {
        if (!$stub && (is_null($data) || is_scalar($data))) $stub = self::STUB;
        if (is_scalar($data)) $data = (object)['id'=>$data];
        
        parent::__construct($data, $stub, $connection);
    }
}
