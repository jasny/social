<?php
/**
 * Facebook Entity
 * 
 * @license MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Facebook;

use Social\Entity as Base;

/**
 * Autoexpending Facebook entity.
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
        if (is_scalar($data)) $data = (object)['id' => $data];
         elseif (is_array($data)) $data = (object)$data;
        
        if (isset($data->metadata)) {
            $data->metadata = (object)$data->metadata;
            if ($data->metadata->type != $this->getType())
                throw new \Exception("Trying to use received Facebook {$data->metadata->type} as " . $this->getType());
            unset($data->metadata);
        }
        
        parent::__construct($data, $stub, $connection);
    }
    
    /**
     * Get the entity type
     * 
     * @return string
     */
    protected function getType()
    {
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])(?![A-Z])/', '_$1', get_class($this)));
    }

    /**
     * Fetch new data of this entity
     */
    public function refresh()
    {
        $this->getConnection()->fetch($this->id);
    }
}
