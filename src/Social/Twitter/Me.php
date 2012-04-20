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
     * @param object|mixed $data        Data or ID/username
     * @param boolean      $stub
     */
    public function __construct(Connection $connection, $data=array(), $stub=false)
    {
        $this->_connection = $connection;
        $this->_type = 'me';
        $this->_stub = $stub || is_null($data) || is_scalar($value);
        
        if (isset($data)) {
            if (is_scalar($data)) $data = $this->makeUserData($data);
            
            // We might need a new connection with my token
            $data = (object)$data;
            if (isset($data->token) && $connection->getAccessToken() != $data->token) {
                $this->_connection = $connection->asUser($data->token, $data->secret, $this);
            }

            $this->setProperties($data);
        }
    }
    
    /**
     * Build request object for fetching or posting.
     * Preparation for a multi request.
     * 
     * @param string $item
     * @param array  $params
     * @return object  { 'method': string, 'url': string, 'params': array }
     */
    public function prepareRequest($item, $params=array())
    {
        switch ($item) {
            case null:                      return (object)array('resource' => 'account/verify_credentials');
            
            case 'timeline':                return (object)array('resource' => 'statuses/home_timeline', 'load' => false);
            case 'mentions':                return (object)array('resource' => 'statuses/mentions', 'load' => false);
            case 'retweeted_by_me':         return (object)array('resource' => 'statuses/retweeted_by_me', 'load' => false);
            case 'retweeted_to_me':         return (object)array('resource' => 'statuses/retweeted_to_me', 'load' => false);
            case 'retweets_of_me':          return (object)array('resource' => 'statuses/retweets_of_me', 'load' => false);
            case 'direct_messages':         return (object)array('resource' => 'direct_messages', 'load' => false);
            case 'send_direct_messages':    return (object)array('resource' => 'direct_messages/send', 'load' => false);
            case 'incomming_friends':       return (object)array('resource' => 'friendships/incoming');
            case 'outgoing_friends':        return (object)array('resource' => 'friendships/outgoing');
            case 'no_retweet_friends':      return (object)array('resource' => 'friendships/no_retweet_ids');
            case 'favorites':               return (object)array('resource' => 'favorites', 'load' => false);
            case 'rate_limit_status':       return (object)array('resource' => 'account/rate_limit_status');
            case 'totals':                  return (object)array('resource' => 'account/totals');
            case 'settings':                return (object)array('resource' => 'account/settings');
            case 'saved_searches':          return (object)array('resource' => 'saved_searches');
            
            case 'update':                  return (object)array('method' => 'POST', 'resource' => 'account/update_profile', 'params' => $this->getParameters(array('name', 'url', 'location', 'description')) + $params);
            case 'update_background_image': return (object)array('method' => 'POST', 'resource' => 'account/update_profile_background_image', 'params' => is_array($params) ? $params + $this->getParameters(array('image', 'tile', 'use'), 'profile_background') : array('image' => $params));
            case 'update_colors':           return (object)array('method' => 'POST', 'resource' => 'account/update_profile_colors', 'params' => $params + $this->getParameters(array('profile_background_color', 'profile_link_color', 'profile_sidebar_border_color', 'profile_sidebar_fill_color', 'profile_text_color')));
            case 'update_background_image': return (object)array('method' => 'POST', 'resource' => 'account/update_profile_background_image', 'params' => is_array($params) ? $params + $this->getParameters(array('image'), 'profile') : array('image' => $params));
            case 'tweet':                   return (object)array('method' => 'POST', 'resource' => 'account/statuses/update' . (is_array($params) && !empty($params['media']) ? '_with_media' : ''), 'params' => is_array($params) ? $params : array('status' => $params));
            case 'send_message':            return (object)array('method' => 'POST', 'resource' => 'direct_messages/new', 'params' => $params);
            case 'follow':                  return (object)array('method' => 'POST', 'resource' => 'friendships/create', 'params' => is_array($params) ? $params : $this->makeUserData($params, true));
            case 'unfollow':                return (object)array('method' => 'POST', 'resource' => 'friendships/destroy', 'params' => is_array($params) ? $params : $this->makeUserData($params, true));
            case 'update_friendship':       return (object)array('method' => 'POST', 'resource' => 'friendships/update', 'params' => $params);
            case 'favorite':                return (object)array('method' => 'POST', 'resource' => 'favorites/create', 'params' => is_array($params) ? $params : array('id' => is_object($params) ? $params->id : $params));
            case 'unfavorite':              return (object)array('method' => 'POST', 'resource' => 'favorites/destroy', 'params' => is_array($params) ? $params : array('id' => is_object($params) ? $params->id : $params));
        }
        
        return parent::prepareRequest($item, $params);
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
        $params = $this->getParameters(array('name', 'url', 'location', 'description'), $params);
        
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
            $params = $this->getParameters(array('image', 'tile', 'use'), $params, 'profile_background');
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
    public function updateImage($params=null)
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
     * @return Status
     */
    public function tweet($status, $media=array(), array $params=array())
    {
        $params['status'] = $status;
        if (!empty($media)) $params['media'] = $media;
        
        return $this->getConnection()->post('statuses/update' . ($media ? '_with_media' : ''), $params);
    }

    /**
     * Send a direct message.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/direct_messages/new
     * 
     * @param string $user    User entity, ID or username
     * @param string $text    The text of the direct message
     * @param array  $params  Additional parameters
     * @return DirectMessage
     */
    public function sendMessage($user, $text, array $params=array())
    {
        $params = $this->makeUserData($user, true) + array('text' => $text) + $params;
        return $this->getConnection()->post('direct_messages/new', $params);
    }
    
    
    /**
     * Follow a user.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/friendships/create
     * 
     * @param User|int|string $user  User entity, ID or username
     * @return User
     */
    public function follow($user)
    {
        $response = $this->getConnection->post('friendships/create', $this->makeUserData($user, true));
        
        if ($user instanceof User) {
            $user->setProperties($response, true);
            return $user;
        }
        
        return $response;
    }
    
    /**
     * Unfollow a user.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/friendships/destroy
     * 
     * @param User|int|string $user    User entity, ID or username
     * @return User
     */
    public function unfollow($user)
    {
        $response = $this->getConnection->post('friendships/destroy', $this->makeUserData($user, true));
        
        if ($user instanceof User) {
            $user->setProperties($response, true);
            return $user;
        }
        
        return $response;
    }

    /**
     * Enable or disable retweets and device notifications from the specified user.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/friendships/update
     * 
     * @param User|int|string $user    User entity, ID or username
     * @param array           $params
     * @return object
     */
    public function updateFriendship($user, array $params)
    {
        return $this->getConnection->post('friendships/update', $this->makeUserData($user, true) + $params);
    }
    

    /**
     * Mark a tweet as favorite.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/favorites/create/%3Aid
     * 
     * @param Status|int $status  Status entity or ID
     * @param array      $params  Additional parameters
     * @return Status
     */
    public function favorite($status, array $params=array())
    {
        $params['id'] = is_scalar($status) ? $status : $status->id;
        return $this->getConnection->post('favorites/create', $params);
    }

    /**
     * Un-favorite a tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/favorites/create/%3Aid
     * 
     * @param Status|int $status  Status entity or ID
     * @param array      $params  Additional parameters
     * @return Status
     */
    public function unfavorite($status, array $params=array())
    {
        $params['id'] = is_scalar($status) ? $status : $status->id;
        return $this->getConnection->post('favorites/destroy', $params);
    }
}