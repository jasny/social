<?php
/**
 * Base class for Collections
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social;

/**
 * An autoexpanding collection.
 */
class CollectionIterator extends \ArrayIterator
{
    /**
     * A copy of the collection object
     * @var Collection
     */
    protected $collection;
    
    
    /**
     * Class contructor.
     * 
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
        parent::__construct($collection);
    }
    
    /**
	 * Move forward to next element.
	 */
	public function next()
    {
        parent::next();
        if (!$this->valid()) $this->collection->loadNext();
    }
}