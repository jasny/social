<?php
/**
 * Twitter direct message entity
 * 
 * @license MIT
 * @copyright 2012-2014 Jasny
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
     * @return DirectMessage $this
     */
    public function refresh()
    {
        return $this->getConnection()->get('direct_messages/show', array('id'=>$this->id), $this);
    }
    
    /**
     * Deletes the saved search.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/direct_messages/destroy
     * 
     * @return DirectMessage $this
     */
    public function destroy()
    {
        return $this->getConnection()->get('direct_messages/destroy', array('id'=>$this->id), $this);
    }
}
