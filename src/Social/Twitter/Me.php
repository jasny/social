<?php
/**
 * Twitter User entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Entity;
use Social\Exception;

/**
 * Autoexpending Twitter entity for the authenticated user.
 * 
 * @see https://dev.twitter.com/docs/api
 * 
 * @property Collection $timeline               statuses/home_timeline
 * @property Collection $mentions               statuses/mentions
 * @property Collection $retweeted_by_me        statuses/retweeted_by_me
 * @property Collection $retweeted_to_me        statuses/retweeted_to_me
 * @property Collection $retweets_of_me         statuses/retweets_of_me
 * @property Collection $direct_messages        direct_messages
 * @property Collection $send_direct_messages   direct_messages/send
 * @property Collection $incomming_friends      friendships/incoming
 * @property Collection $outgoing_friends       friendships/outgoing
 * @property Collection $no_retweet_friends     friendships/no_retweet_ids
 * @property Collection $favorites              favorites
 * @property object     $rate_limit_status      rate_limit_status
 * @property object     $totals                 totals
 * @property Settings   $settings               settings
 * @property Collection $saved_searches         saved_searches
 */
class Me extends User
{
    /**
     * Class constructor
     * 
     * @param Connection   $connection
     * @param string       $type
     * @param object|mixed $data        Data or ID
     * @param boolean      $stub
     */
    public function __construct(Connection $connection, $data=array(), $stub=false)
    {
        $this->_connection = $connection;
        $this->_type = 'me';
        $this->_stub = $stub || is_null($data) || is_scalar($value);
        
        if (isset($data)) {
            if (is_scalar($data)) {
                $key = is_int($data) || ctype_digit($data) ? 'id' : 'screen_name';
                $data = array($key => $data);
            }
            $this->setProperties($data);
        }
    }
    
    /**
     * Fetch new data from Facebook.
     * 
     * @param array   $params
     * @param boolean $expand  Ignored, will always expand
     * @return Entity  $this
     */
    public function reload(array $params=array(), $expand=true)
    {
        $data = $this->getConnection()->getData('account/verify_credentials', $params);
        $this->setProperties($data, false);
        return $this;
    }
    
    /**
     * Get subdata from Twitter.
     * 
     * @param string $item
     * @param array  $params
     * @return Collection|mixed
     */
    public function fetch($item, array $params=array())
    {
        switch ($item) {
            case 'timeline':               $resource = 'statuses/home_timeline'; break;
            case 'mentions':               $resource = 'statuses/mentions'; break;
            case 'retweeted_by_me':        $resource = 'statuses/retweeted_by_me'; break;
            case 'retweeted_to_me':        $resource = 'statuses/retweeted_to_me'; break;
            case 'retweets_of_me':         $resource = 'statuses/retweets_of_me'; break;
            case 'direct_messages':        $resource = 'direct_messages'; break;
            case 'send_direct_messages':   $resource = 'direct_messages/send'; break;
            case 'incomming_friends':      $resource = 'friendships/incoming'; break;
            case 'outgoing_friends':       $resource = 'friendships/outgoing'; break;
            case 'no_retweet_friends':     $resource = 'friendships/no_retweet_ids'; break;
            case 'favorites':              $resource = 'favorites'; break;
            case 'rate_limit_status':      $resource = 'account/rate_limit_status'; break;
            case 'totals':                 $resource = 'account/totals'; break;
            case 'settings':               $resource = 'account/settings'; break;
            case 'saved_searches':         $resource = 'saved_searches'; break;
        }
        
        if (!isset($resource)) return parent::fetch($item, $params);
        
        $this->$item = $this->getConnection()->get($resource, $params);
        return $this->$item;
    }
    
    
    /**
     * Update users profile information.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/account/update_profile
     * 
     * @param array $params  If omitted parameter are taken from the entity
     * @return Me  $this
     */
    public function update(array $params=array())
    {
        $fields = array('name', 'url', 'location', 'description');
        $params += array_intersect_key((array)$this, array_fill_keys($fields, null));
        
        $data = $this->getConnection()->postData('account/update_profile', $params);
        $this->setProperties($data, false);
        
        return $this;
    }

    /**
     * Update users profile background image.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/account/update_profile_background_image
     * 
     * @param array|string $params   Parameters or raw image, if omitted it's taken from the entity.
     * @return Me  $this
     */
    public function updateBackgroundImage($params=array())
    {
        if (is_string($params)) {
            $params = array('image' => $params);
        } else {
            $fields = array('profile_background_image', 'profile_background_tile', 'profile_background_use');
            $values = array_intersect_key((array)$this, array_fill_keys($fields, null));
        
            foreach ($values as $key=>$value) {
                $key = substr($key, 18);
                if (!isset($params[$key])) $params[$key] = $value;
            }
        }
        
        $data = $this->getConnection()->postData('account/update_profile_background_image', $params);
        $this->setProperties($data, false);
        
        return $this;
    }
    
    /**
     * Update users profile colors.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/account/update_profile_colors
     * 
     * @param array $params  If omitted the parameters are taken from the entity
     * @return Me  $this
     */
    public function updateColors($params=null)
    {
        if (!isset($params)) {
            $fields = array('profile_background_color', 'profile_link_color', 'profile_sidebar_border_color', 'profile_sidebar_fill_color', 'profile_text_color');
            $params = array_intersect_key((array)$this, array_fill_keys($fields, null));
        }
        
        $data = $this->getConnection()->postData('account/update_profile_colors', $params);
        $this->setProperties($data, false);
        
        return $this;
    }
    
    /**
     * Update users profile image.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/account/update_profile_image
     * 
     * @param array|string $params   Parameters or raw image, if omitted it's taken from the entity.
     * @return Me  $this
     */
    public function updateBackgroundImage($params=null)
    {
        if (is_string($params)) {
            $params = array('image' => $params);
        } elseif (!isset($params['image']) && property_exists($this, 'profile_image')) {
            $params['image'] = $this->profile_image;
        }
        
        $data = $this->getConnection()->postData('account/update_profile_image', $params);
        $this->setProperties($data, false);
        
        return $this;
    }
    
    
    /**
     * Send a tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/statuses/update
     * @see https://dev.twitter.com/docs/api/1/post/statuses/update_with_media
     * 
     * @param string $status  The status message
     * @param array  $media   Images
     * @param array  $params  Additional parameters
     */
    public function tweet($status, $media=array(), array $params=array())
    {
        $params['status'] = $status;
        if (!empty($media)) $params['media'] = $media;
        
        return $this->getConnection()->post('statuses/update' . ($media ? '_with_media' : ''), $params);
    }
    
    
    /**
     * Follow a Twitter
     * 
     * @param User|int|string $user  User entity, ID or username
     */
    public function follow($user)
    {
        
    }
}