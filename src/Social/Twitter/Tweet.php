<?php
/**
 * Twitter tweet entity
 * 
 * @license MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Twitter;

/**
 * Twitter tweet entity.
 */
class Tweet extends Entity
{
    /**
     * Fetch data of this tweet (if it's a stub).
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/statuses/show/%3Aid
     * 
     * @param boolean $refresh  Fetch new data, even if this isn't a stub
     * @return Tweet $this
     */
    public function fetch($refresh=false)
    {
        if ($refresh || $this->isStub()) $this->getConnection()->get('statuses/show/:id', array(':id'=>$this->id), $this);
        return $this;
    }
    
    /**
     * Returns information allowing the creation of an embedded representation of a Tweet on third party sites. 
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/statuses/oembed
     * 
     * @params array $params
     * @return object
     */
    public function getOEmbed(array $params=array())
    {
        return $this->getConnection()->get('statuses/oembed', $this->getParams() + $params);
    }
    
    /**
     * Returns up to 100 of the first retweets of a given tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/statuses/oembed
     * 
     * @params array $params
     * @return object
     */
    public function getRetweets(array $params=array())
    {
        return $this->getConnection()->get('statuses/retweets/:id', array(':id'=>$this->id) + $params);
    }
    
    /**
     * Delete the tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/statuses/oembed
     * 
     * @return Tweet $this
     */
    public function destroy()
    {
        return $this->getConnection()->get('statuses/destroy/:id', array(':id'=>$this->id), $this);
    }
    
    
    /**
     * Check if this tweet is the same as the given one.
     * 
     * @param Tweet|string $tweet  Tweet entity or id
     * @return boolean
     */
    public function is($tweet)
    {
        if (is_array($tweet)) $tweet = (object)$tweet;
        return $this->id == (is_scalar($tweet) ? $tweet : $tweet->id); 
    }
    
    /**
     * Get id in array.
     * 
     * @return array
     */
    public function asParams()
    {
        return array('id'=>$this->id);
    }
    
    /**
     * Cast to tweet to string.
     */
    public function __toString()
    {
        return $this->message;
    }
}