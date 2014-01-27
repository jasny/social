<?php
/**
 * Twitter saved search entity
 * 
 * @license MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Twitter;

/**
 * Twitter saved search entity.
 */
class SavedSearch extends Entity
{
    /**
     * Fetch data of this saved search (if it's a stub).
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/saved_searches/show/%3Aid
     * 
     * @return SavedSearch $this
     */
    public function refresh()
    {
        return $this->getConnection()->get('saved_searches/show/:id', array(':id'=>$this->id), $this);
    }
    
    /**
     * Deletes the saved search.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/saved_searches/destroy/%3Aid
     * 
     * @return SavedSearch $this
     */
    public function destroy()
    {
        return $this->getConnection()->get('statuses/destroy/:id', array(':id'=>$this->id), $this);
    }
    
    /**
     * Check if this savedSearch is the same as the given one.
     * 
     * @param SavedSearch|string $savedSearch  SavedSearch entity or id
     * @return boolean
     */
    public function is($savedSearch)
    {
        if (is_array($savedSearch)) $savedSearch = (object)$savedSearch;
        return $this->id == (is_scalar($savedSearch) ? $savedSearch : $savedSearch->id); 
    }
}