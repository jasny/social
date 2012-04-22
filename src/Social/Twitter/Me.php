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
 * @property Tweet[]         $timeline               statuses/home_timeline
 * @property Tweet[]         $mentions               statuses/mentions
 * @property Tweet[]         $retweeted_by_me        statuses/retweeted_by_me
 * @property Tweet[]         $retweeted_to_me        statuses/retweeted_to_me
 * @property Tweet[]         $retweets_of_me         statuses/retweets_of_me
 * @property DirectMessage[] $direct_messages        direct_messages
 * @property DirectMessage[] $send_direct_messages   direct_messages/send
 * @property User[]          $incomming_friends      friendships/incoming
 * @property User[]          $outgoing_friends       friendships/outgoing
 * @property User[]          $no_retweet_friends     friendships/no_retweet_ids
 * @property Tweet[]         $favorites              favorites
 * @property object          $rate_limit_status      rate_limit_status
 * @property object          $totals                 totals
 * @property Settings        $settings               settings
 * @property Collection      $saved_searches         saved_searches
 */
class Me extends User
{
    /**
     * Class constructor.
     * 
     * @param Connection   $connection
     * @param string       $type
     * @param object|mixed $data        Data or ID/username; Caution: We don't verify the id/username against the access token.
     * @param boolean      $stub
     */
    public function __construct(Connection $connection, $data=array(), $stub=false)
    {
        $this->_connection = $connection;
        $this->_type = 'me';
        $this->_stub = $stub || is_null($data) || is_scalar($value);
        
        if (isset($data)) {
            if (is_scalar($data)) {
                $data = $this->makeUserData($data);
            } else {
                // We might need a new connection with my token
                $data = (object)$data;
                
                if (property_exists($data, 'token') && $connection->getAccessToken() != $data->token) {
                    $this->_connection = $connection->asUser($data->token, $data->secret, $this);
                }
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
            
            case 'timeline':                return (object)array('resource' => 'statuses/home_timeline', 'lazy' => true);
            case 'mentions':                return (object)array('resource' => 'statuses/mentions', 'lazy' => true);
            case 'retweeted_by_me':         return (object)array('resource' => 'statuses/retweeted_by_me', 'lazy' => true);
            case 'retweeted_to_me':         return (object)array('resource' => 'statuses/retweeted_to_me', 'lazy' => true);
            case 'retweets_of_me':          return (object)array('resource' => 'statuses/retweets_of_me', 'lazy' => true);
            case 'direct_messages':         return (object)array('resource' => 'direct_messages', 'lazy' => true);
            case 'send_direct_messages':    return (object)array('resource' => 'direct_messages/send', 'lazy' => true);
            case 'incomming_friends':       return (object)array('resource' => 'friendships/incoming');
            case 'outgoing_friends':        return (object)array('resource' => 'friendships/outgoing');
            case 'no_retweet_friends':      return (object)array('resource' => 'friendships/no_retweet_ids');
            case 'favorites':               return (object)array('resource' => 'favorites', 'lazy' => true);
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
            case 'create_list':             return (object)array('method' => 'POST', 'resource' => 'lists/create', 'params' => is_array($params) ? $params : array('name' => $params));
            case 'subscribe':               return (object)array('method' => 'POST', 'resource' => 'lists/subscribers/create', 'params' => is_array($params) ? $params : array('list_id' => is_object($params) ? $params->id : $params));
            case 'unsubscribe':             return (object)array('method' => 'POST', 'resource' => 'lists/subscribers/destroy', 'params' => is_array($params) ? $params : array('list_id' => is_object($params) ? $params->id : $params));
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
     * @param string $tweet   The status message
     * @param array  $media   Images
     * @param array  $params  Additional parameters
     * @return Tweet
     */
    public function tweet($tweet, $media=array(), array $params=array())
    {
        $params['status'] = $tweet;
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
     * Follow a user / users.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/friendships/create
     * 
     * @param User|int|string|array $userlist  User entity/ID/username or array with user entites/IDs/usernames
     * @return User|Collection
     */
    public function follow($userlist)
    {
        // Single user
        if (!is_array($userlist) && !$userlist instanceof \ArrayObject) {
            $user = $userlist;
            $response = $this->getConnection->post('friendships/create', $this->makeUserData($user, true));
        
            if (!$user instanceof User) return $response;
            
            $user->setProperties($response, true);
            return $user;
        }
        
        // Multiple users
        $entities = array();
        foreach ($userlist as $i=>$user) {
            if (is_object($user)) $entities[$i] = $user;
            $requests[$i] = (object)array('method' => 'POST', 'url' => 'friendships/create', $this->makeUserData($user, true));
        }
        
        $results = $this->_connection->multiRequest($requests);
        
        foreach ($entities as $i=>$user) {
            if (!isset($results[$i])) continue;
            
            $user->setProperties($results[$i], true);
            $results[$i] = $user;
        }
        
        return $results;
    }
    
    /**
     * Unfollow a user / users.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/friendships/destroy
     * 
     * @param User|int|string|array $userlist  User entity/ID/username or array with user entites/IDs/usernames
     * @return User|Collection
     */
    public function unfollow($userlist)
    {
        // Single user
        if (!is_array($userlist) && !$userlist instanceof \ArrayObject) {
            $user = $userlist;
            $response = $this->getConnection->post('friendships/destroy', $this->makeUserData($user, true));

            if (!$user instanceof User) return $response;
            
            $user->setProperties($response, true);
            return $user;
        }
        
        // Multiple users
        $entities = array();
        foreach ($userlist as $i=>$user) {
            if (is_object($user)) $entities[$i] = $user;
            $requests[$i] = (object)array('method' => 'POST', 'url' => 'friendships/destroy', $this->makeUserData($user, true));
        }
        
        $results = $this->_connection->multiRequest($requests);
        
        foreach ($entities as $i=>$user) {
            if (!isset($results[$i])) continue;
            
            $user->setProperties($results[$i], true);
            $results[$i] = $user;
        }
        
        return $results;
    }

    /**
     * Enable or disable retweets and device notifications from the specified user(s).
     * 
     * @see https://dev.twitter.com/docs/api/1/post/friendships/update
     * 
     * @param User|int|string|array $userlist  User entity/ID/username or array with user entites/IDs/usernames
     * @param array           $params
     * @return object|Collection
     */
    public function updateFriendship($userlist, array $params)
    {
        // Single user
        if (!is_array($userlist) && !$userlist instanceof \ArrayObject) {
            $user = $userlist;
            return $this->getConnection->post('friendships/update', $this->makeUserData($user, true) + $params);
        }
        
        // Multiple users
        foreach ($userlist as $user) {
            $requests[] = (object)array('method' => 'POST', 'url' => 'friendships/update', $this->makeUserData($user, true) + $params);
        }
        
        return $this->_connection->multiRequest($requests);
    }
    
    /**
     * Get the relationship with the user(s).
     * Values for connections can be: 'following', 'following_requested', 'followed_by', 'none'.
     * 
     * @see https://dev.twitter.com/docs/api/1/get/friendships/lookup
     * 
     * @param User|int|string|array $userlist  User entity/ID/username or array with user entites/IDs/usernames
     * @return User|Collection
     */
    public function lookupFriendship($userlist)
    {
        // Single user
        if (!is_array($userlist) && !$userlist instanceof \ArrayObject) {
            $user = $userlist;
            
            if (is_object($user)) {
                $params = $user->asParams();
            } else {
                $key = is_int($user) || ctype_digit($user) ? 'id' : 'screen_name';
                $params = array($key => $user);
            }
            
            $result = $this->getConnection()->get('friendships/lookup', $params);
            $entity = $result[0];
            
            if (is_object($user)) $entity->setProperties($user, true);
            return $entity;
        }
        
        // Multiple users (1 request per 100 users)
        foreach ($userlist as $user) {
            if (is_object($user)) $key = property_exists($user, 'id') ? 'id' : 'screen_name';
              else $key = is_int($user) || ctype_digit($user) ? 'id' : 'screen_name';
            
            if ($key == 'id') {
                if (is_object($user)) {
                    $ids[] = $user->id;
                    $entities[$user->id] = $user;
                } else {
                    $ids[] = $user;
                }

                if (count($ids) >= 100) {
                    $requests[] = (object)array('method' => 'POST', 'url' => 'friendships/lookup', array('user_id' => $ids));
                    $ids = array();
                }

            } else {
                if (is_object($user)) {
                    $names[] = $user->screen_name;
                    $entities[$user->screen_name] = $user;
                } else {
                    $names[] = $user;
                }

                if (count($names) >= 100) {
                    $requests[] = (object)array('method' => 'POST', 'url' => 'friendships/lookup', array('screen_name' => $names));
                    $names = array();
                }
            }
        }

        if (!empty($ids)) $requests[] = (object)array('method' => 'POST', 'url' => 'friendships/lookup', array('user_id' => $ids) + $params);
        if (!empty($names)) $requests[] = (object)array('method' => 'POST', 'url' => 'friendships/lookup', array('screen_name' => $names) + $params);
        
        $users = array();
        $results = $this->_connection->multiRequest($requests);
        
        foreach ($results as $result) {
            foreach ($result as $user) {
                if (isset($entities[$user->id])) $user->setProperties($entities[$user->id], true);
                  elseif (isset($entities[$user->screen_name])) $user->setProperties($entities[$user->screen_name], true);
                
                $users[] = $user;
            }
        }

        return new Collection($this->getConnection(), 'user', $users);
    }
    
    
    /**
     * Mark a tweet as favorite.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/favorites/create/%3Aid
     * 
     * @param Tweet|int  $tweet   Tweet entity or ID
     * @param array      $params  Additional parameters
     * @return Tweet
     */
    public function favorite($tweet, array $params=array())
    {
        $params['id'] = is_object($tweet) ? $tweet->id : $tweet;
        return $this->getConnection->post('favorites/create', $params);
    }

    /**
     * Un-favorite a tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/favorites/create/%3Aid
     * 
     * @param Tweet|int  $tweet   Tweet entity or ID
     * @param array      $params  Additional parameters
     * @return Tweet
     */
    public function unfavorite($tweet, array $params=array())
    {
        $params['id'] = is_object($tweet) ? $tweet->id : $tweet;
        return $this->getConnection->post('favorites/destroy', $params);
    }
    
    
    /**
     * Create a new list.
     * 
     * @see https://dev.twitter.com/docs/api/1/post/lists/create
     * 
     * @param array|string $params  Parameters or name
     * @return List
     */
    public function createList($params=array())
    {
        if (!is_array($params)) $params = array('name' => $params);
        return $this->getConnection->post('lists/create', $params);
    }
    
    /**
     * Subscribe to a list.
     * 
     * https://dev.twitter.com/docs/api/1/post/lists/subscribers/create
     * 
     * @param List|int|array $list  List entity/id or params
     * @return List
     */
    public function subscribe($list)
    {
        $params = is_array($list) ? $list : array('list_id' => is_object($list) ? $list->id : $list);
        return $this->getConnection()->post('lists/subscribers/create', $params);
    }

    /**
     * Unsubscribe from a list.
     * 
     * https://dev.twitter.com/docs/api/1/post/lists/subscribers/create
     * 
     * @param List|int|array $list  List entity/id or params
     * @return List
     */
    public function unsubscribe($list)
    {
        $params = is_array($list) ? $list : array('list_id' => is_object($list) ? $list->id : $list);
        return $this->getConnection()->post('lists/subscribers/destroy', $params);
    }
}
