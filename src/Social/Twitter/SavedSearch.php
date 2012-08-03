<?php
/**
 * Twitter saved search entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Exception;

/**
 * Autoexpending Twitter saved search entity.
 * 
 */
class SavedSearch extends Entity
{
    /**
     * Build request object for fetching or posting.
     * Preparation for a multi request.
     * 
     * @param string $action  Action or fetch item
     * @param mixed  $target  Entity/id
     * @param array  $params
     * @return object  { 'method': string, 'url': string, 'params': array }
     */
    public function prepareRequest($action, $target=null, array $params=array())
    {
    }    
}