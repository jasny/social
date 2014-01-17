<?php
/**
 * Twitter place entity
 * 
 * @license MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Twitter;

/**
 * Twitter place entity.
 */
class Place extends Entity
{
    /**
     * Fetch data of this place (if it's a stub).
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/geo/id/%3Aplace_id
     * 
     * @param boolean $refresh  Fetch new data, even if this isn't a stub
     * @return Place $this
     */
    public function fetch($refresh=false)
    {
        if ($refresh || $this->isStub()) $this->getConnection()->get('geo/id/:place_id', array(':place_id'=>$this->id), $this);
        return $this;
    }
    
    /**
     * Check if this savedSearch is the same as the given one.
     * 
     * @param Place|string $place  Place entity or id
     * @return boolean
     */
    public function is($place)
    {
        if (is_array($place)) $place = (object)$place;
        return $this->id == (is_scalar($place) ? $place : $place->id); 
    }
}