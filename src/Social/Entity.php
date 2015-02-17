<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social;

/**
 * Base class for entities.
 */
abstract class Entity
{
    /**
     * Class constructor
     * 
     * @param array|object $data
     */
    public function __construct($data)
    {
        foreach ($data as $key=>&$value) {
            $this->$key = $value;
        }
    }
    
    /**
     * Get the unique identifier of the entity.
     * 
     * @return mixed
     */
    abstract public function getId();
}
