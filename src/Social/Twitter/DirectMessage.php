<?php
/**
 * Twitter direct message entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

/**
 * Twitter direct message entity.
 */
class DirectMessage extends Entity
{
    /**
     * Fetch data of this direct message (if it's a stub).
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/direct_messages/show
     * 
     * @param boolean $refresh  Fetch new data, even if this isn't a stub
     * @return SavedSearch $this
     */
    public function fetch($refresh=false)
    {
        if ($refresh || $this->isStub()) $this->getConnection()->get('direct_messages/show', array('id'=>$this->id), $this);
        return $this;
    }
    
    /**
     * Deletes the saved search.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/direct_messages/destroy
     * 
     * @return SavedSearch $this
     */
    public function destroy()
    {
        return $this->getConnection()->get('direct_messages/destroy', array('id'=>$this->id), $this);
    }
    
    /**
     * Check if this tweet is the same as the given one.
     * 
     * @param DirectMessage|string $directMessage  DirectMessage entity or id
     * @return boolean
     */
    public function is($directMessage)
    {
        if (is_array($directMessage)) $directMessage = (object)$directMessage;
        return $this->id == (is_scalar($directMessage) ? $directMessage : $directMessage->id); 
    }
}
