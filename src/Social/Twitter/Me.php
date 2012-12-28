<?php
/**
 * Twitter User entity
 * 
 * @license MIT
 * @copyright 2012 Jasny
 */

/** */
namespace Social\Twitter;

use Social\Result;

/**
 * Autoexpending Twitter entity for the authenticated user.
 * 
 * @see https://dev.twitter.com/docs/api/1.1/get/account/verify_credentials
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
    public function __construct(Connection $connection, $data=array(), $stub=self::NO_STUB)
    {
        $this->_connection = $connection;
        $this->_type = 'me';
        $this->_stub = $stub || is_null($data) || is_scalar($data);
        
        if (isset($data)) {
            if (is_scalar($data)) {
                $data = self::makeUserData($data);
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
     * Expand if this is a stub.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/account/verify_credentials
     * 
     * @param boolean $force  Fetch new data, even if this isn't a stub
     * @return Me  $this
     */
    public function expand($force=false)
    {
        if ($force || $this->isStub()) $this->getConnection()->get('account/verify_credentials', array(), $this);
        return $this;
    }
    
    
    /**
     * Update users profile information.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/account/update_profile
     * 
     * @param array $params
     * @return Me  $this
     */
    public function updateProfile(array $params)
    {
        return $this->getConnection()->post('account/update_profile', $params, $this);
    }

    /**
     * Update users profile background image.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/account/update_profile_background_image
     * 
     * @param string|array $image  Raw image or parameters
     * @return Me  $this
     */
    public function updateProfileBackgroundImage($image)
    {
        return $this->getConnection()->post('account/update_profile_background_image', is_string($image) ? compact('image') : $image, $this);
    }
    
    /**
     * Update users profile colors.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/account/update_profile_colors
     * 
     * @param array $params
     * @return Me  $this
     */
    public function updateProfileColors($params)
    {
        return $this->getConnection()->post('account/update_profile_colors', $params, $this);
    }
    
    /**
     * Update users profile image.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/account/update_profile_image
     * 
     * @param string|array $image  Raw image or parameters
     * @return Me  $this
     */
    public function updateProfileImage($image)
    {
        return $this->getConnection()->post('account/update_profile_image', is_string($image) ? compact('image') : $image, $this);
    }
    
    /**
     * Uploads a profile banner on behalf of the authenticating user.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/account/update_profile_banner
     * 
     * @param string|array $image  Raw image or parameters
     * @return Me  $this
     */
    public function updateProfileBanner($image)
    {
        return $this->getConnection()->post('account/update_profile_banner', is_string($image) ? compact('image') : $image, $this);
    }
    
    
    /**
     * Send a tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/statuses/update
     * @see https://dev.twitter.com/docs/api/1.1/post/statuses/update_with_media
     * 
     * @param string $tweet   The status message
     * @param array  $media   Images
     * @param array  $params  Additional parameters
     * @return Tweet
     */
    public function tweet($tweet, $media=array(), array $params=array())
    {
        $params['status'] = $tweet;
        if (!empty($media)) $params['media'] = (array)$media;
        
        return $this->getConnection()->post('statuses/update' . ($media ? '_with_media' : ''), $params);
    }

    /**
     * Returns a collection of the most recent Tweets and retweets posted by the authenticating user and the users they follow. 
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/statuses/home_timeline
     * 
     * @return Collection of tweets
     */
    public function getTimeline(array $params=array())
    {
        return $this->getConnection()->get('statuses/home_timeline', $params);
    }

    /**
     * Returns the most recent mentions (tweets containing a users's screen_name) for the authenticating user.
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/statuses/mentions_timeline
     * 
     * @return Collection of tweets
     */
    public function getMentions(array $params=array())
    {
        return $this->getConnection()->get('statuses/mentions_timeline', $params);
    }

    /**
     * Returns the most recent tweets authored by the authenticating user that have recently been retweeted by others.
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/statuses/retweets_of_me
     * 
     * @return Collection of tweets
     */
    public function getRetweetsOfMe(array $params=array())
    {
        return $this->getConnection()->get('statuses/retweets_of_me', $params);
    }
    
    
    /**
     * Returns the most recent direct messages sent to the authenticating user. Includes detailed information about the sender and recipient user.
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/direct_messages
     * 
     * @return Collection of direct messages
     */
    public function getDirectMessages(array $params=array())
    {
        return $this->getConnection()->get('direct_messages', $params);
    }

    /**
     * Returns the most recent direct messages sent by the authenticating user. Includes detailed information about the sender and recipient user.
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/direct_messages/send
     * 
     * @return Collection of direct messages
     */
    public function getSendDirectMessages(array $params=array())
    {
        return $this->getConnection()->get('direct_messages/send', $params);
    }

    /**
     * Send a direct message.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/direct_messages/new
     * 
     * @param mixed  $user    User entity/ID/username or array with users
     * @param string $text    The text of the direct message
     * @param array  $params  Additional parameters
     * @return DirectMessage
     */
    public function sendDirectMessage($user, $text, array $params=array())
    {
        $params = self::makeUserData($user, true) + array('text' => $text) + $params;
        return $this->getConnection()->post('direct_messages/new', $params);
    }

    
    /**
     * Perform an action for a set of users.
     * 
     * @param string $action
     * @param mixed  $user    User entity/ID/username or array with users
     * @param array  $params
     * @return User|Collection
     */
    protected function forUsers($action, $user, array $params=array())
    {
        // Single user
        if (!is_array($user) && !$user instanceof \ArrayObject) {
            return $this->getConnection()->post($action, self::makeUserData($user, true) + $params, $user instanceof User ? $user : true);
        }
        
        // Multiple users
        $this->getConnection()->prepare();
        
        foreach ($user as $u) {
            $this->getConnection()->post($action, self::makeUserData($u, true) + $params, $u instanceof User ? $u : true);
        }
        
        return $this->getConnection()->execute();
    }

    /**
     * Follow a user/users.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/friendships/create
     * 
     * @param mixed $user  User entity/ID/username or array with users
     * @return User|Collection
     */
    public function follow($user)
    {
        return $this->forUsers('friendships/create', $user);
    }
    
    /**
     * Unfollow a user/users.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/friendships/destroy
     * 
     * @param mixed $user  User entity/ID/username or array with users
     * @return User|Collection
     */
    public function unfollow($user)
    {
        return $this->forUsers('friendships/destroy', $user);
    }
    
    /**
     * Block a user.
     * 
     * https://dev.twitter.com/docs/api/1.1/post/blocks/create
     * 
     * @param mixed $user  User entity/ID/username or array with users
     * @return User|Collection
     */
    public function block($user)
    {
        return $this->forUsers('blocks/create', $user);
    }

    /**
     * Unblock a user.
     * 
     * https://dev.twitter.com/docs/api/1.1/post/blocks/destroy
     * 
     * @param mixed $user  User entity/ID/username or array with users
     * @return User|Collection
     */
    public function unblock($user)
    {
        return $this->forUsers('blocks/destroy', $user);
    }
    
    /**
     * Enable or disable retweets and device notifications from the specified user(s).
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/friendships/update
     * 
     * @param mixed $user    User entity/ID/username or array with users
     * @param array $params
     * @return User|Collection
     */
    public function updateFriendship($user, array $params)
    {
        return $this->forUsers('blocks/update', $user, $params);
    }
    
    /**
     * Get the relationship between me and the user(s).
     * 
     * Results in value object(s) with the following properties 'following', 'following_requested', 'followed_by'.
     * Optionally also include extra properties: 'notifications_enabled', 'can_dm', 'want_retweets', 'marked_spam', 'all_replies', 'blocking'.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/friendships/lookup
     * @see https://dev.twitter.com/docs/api/1.1/get/friendships/show
     * 
     * @param mixed   $user      User entity/ID/username or array with users
     * @param boolean $extended  Include additional info
     * @return User|Result
     */
    public function getFriendship($user, $extended=false)
    {
        $key = null;
        if ($extended === 'following' || $extended === 'followed_by') {
            $key = $extended;
            $extended = false;
        }
        
        if ($extended) return parent::getFriendship($user);
        
        // Single user
        if (!is_array($user) && !$user instanceof \ArrayObject) {
            if (!isset($fn)) $fn = function ($result) use ($user, $key) { return Me::processLookupFriendship($result, $user, $key); };
            
            $results = $this->getConnection()->get('friendships/lookup', self::makeUserData($user, true), $fn);
            return $results[0][1];
        }
        
        // Multiple users (1 request per 100 users)
        foreach ($user as $u) {
            if (!is_object($u)) $u = new User($this->connection, $u, Entity::STUB);
            $key = property_exists($u, 'id') ? 'id' : 'screen_name';
            
            if ($key == 'id') {
                $ids[] = $u->id;
                $users[$u->id] = $u;
            } else {
                $names[] = $u->screen_name;
                $users[$u->screen_name] = $u;
            }
        }

        if (!isset($fn)) $fn = function ($result) use (&$users, $key) { return Me::processLookupFriendship($result, $users, $key); };
        
        $this->getConnection()->prepare(new Result($this->getConnection()));
        
        foreach (array_chunk($ids, 100) as $chunk) $this->getConnection()->post('friendships/lookup', array('user_id' => $chunk), $fn);
        foreach (array_chunk($names, 100) as $chunk) $this->getConnection()->post('friendships/lookup', array('screen_name' => $chunk), $fn);
        
        return $this->getConnection()->execute();
    }
    
    /**
     * Convert the result of friendships/lookup.
     * 
     * @param array  $result
     * @param array  $users
     * @param string $key
     * @return array
     */
    protected static function convertLookupFriendship($result, $users, $key)
    {
        $friendship = (object)array(
            'following' => in_array('following', $result->connections),
            'following_requested' => in_array('following_requested', $result->connections),
            'followed_by' => in_array('followed_by', $result->connections)
        );
        unset($result->connections);

        $user = !is_array($users) ? $users : (isset($users[$result->id]) ? $users[$result->id] : $users[$result->screen_name]);
        if ($user instanceof User && $user->isStub()) $user->setProperties($result);

        return array($user, $key ? $friendship->$key : $friendship);
    }
    
    
    /**
     * Returns a collection of users who has a pending request to follow the authenticating user.
     * Only if the authenticating user has a protected account.
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/friendships/incoming
     * 
     * @return Collection of users
     */
    public function getPendingFollowers(array $params=array())
    {
        return $this->getConnection()->get('friendships/incoming', $params);
    }

    /**
     * Returns a collection of protected users for whom the authenticating user has a pending follow request.
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/friendships/outgoing
     * 
     * @return Collection of users
     */
    public function getPendingFollowRequests(array $params=array())
    {
        return $this->getConnection()->get('friendships/outgoing', $params);
    }
    
    
    /**
     * Mark a tweet as favorite.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/favorites/create/%3Aid
     * 
     * @param Tweet|int  $tweet   Tweet entity/ID
     * @param array      $params  Additional parameters
     * @return Tweet
     */
    public function favorite($tweet, array $params=array())
    {
        $params['id'] = is_object($tweet) ? $tweet->id : $tweet;
        return $this->getConnection()->post('favorites/create', $params);
    }

    /**
     * Un-favorite a tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/favorites/destroy/%3Aid
     * 
     * @param Tweet|int  $tweet   Tweet entity/ID
     * @param array      $params  Additional parameters
     * @return Tweet
     */
    public function unfavorite($tweet, array $params=array())
    {
        $params['id'] = is_object($tweet) ? $tweet->id : $tweet;
        return $this->getConnection()->post('favorites/destroy', $params);
    }
    
    /**
     * Returns the most recent Tweets favorited by the authenticating or specified user.
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/favorites
     * 
     * @return Collection of tweets
     */
    public function getFavorites(array $params=array())
    {
        return $this->getConnection()->get('favorites', $params);
    }
    
    
    /**
     * Create a new list.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/lists/create
     * 
     * @param array|string $params  Parameters or list name
     * @return UserList
     */
    public function createList($params=array())
    {
        if (!is_array($params)) $params = array('name' => $params);
        return $this->getConnection()->post('lists/create', $params);
    }
    
    /**
     * Perform an action for a set of lists.
     * 
     * @param string $action
     * @param mixed  $list    UserList entity/ID or array with lists or params
     * @return UserList|Collection
     */
    protected function forLists($action, $list)
    {
        // Single list
        if (!is_array($list) || is_string(key($list))) {
            $params = is_array($list) ? $list : ($list instanceof UserList ? $list->asParams() : array('list_id' => $list));
            return $this->getConnection()->post($action, $params, $list instanceof \ArrayObject ? $list : true);
        }

        // Multiple lists
        $this->prepare();
        
        foreach ($list as $l) {
            $params = $l instanceof UserList ? $l->asParams() : array('list_id' => $list);
            $this->getConnection()->post($action, $params, $l instanceof \ArrayObject ? $l : true);
        }
        
        return $this->execute();
    }

    /**
     * Subscribe to a list.
     * 
     * https://dev.twitter.com/docs/api/1.1/post/lists/subscribers/create
     * 
     * @param UserList|int|array $list  UserList entity/ID or array with lists or params
     * @return UserList|Collection
     */
    public function subscribe($list)
    {
        return $this->forLists('lists/subscribers/create', $list);
    }

    /**
     * Unsubscribe from a list.
     * 
     * https://dev.twitter.com/docs/api/1.1/post/lists/subscribers/create
     * 
     * @param UserList|int|array $list  UserList entity/ID or array with lists or params
     * @return UserList|Collection
     */
    public function unsubscribe($list)
    {
        return $this->forLists('lists/subscribers/destroy', $list);
    }
    
    
    /**
     * Create a new saved search for the authenticated user.
     *
     * @see https://dev.twitter.com/docs/api/1.1/post/saved_searches/create
     * 
     * @param string $query
     * @return SavedSearch
     */
    public function saveSearch($query)
    {
        return $this->getConnection()->post('saved_searches/create', is_array($query) ? $query : compact('query'));
    }

    /**
     * Returns the authenticated user's saved search queries.
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/saved_searches/list
     * 
     * @return Collection of saved searches
     */
    public function getSavedSearches()
    {
        return $this->getConnection()->get('saved_searches/list');
    }
    
    
    /**
     * Get authenticated user's Settings.
     *
     * @see https://dev.twitter.com/docs/api/1.1/get/account/settings
     * 
     * @return object
     */
    public function getSettings()
    {
        return $this->getConnection()->get('account/settings');
    }
    
    /**
     * Sets which device Twitter delivers updates to for the authenticating user. Sending none as the device parameter will disable SMS updates.
     *
     * @see https://dev.twitter.com/docs/api/1.1/post/account/update_delivery_device
     * 
     * @param string $device
     * @return Me  $this
     */
    public function updateDeliveryDevice($device)
    {
        return $this->getConnection()->post('account/update_delivery_device', is_string($device) ? compact('device') : $device, $this);
    }    
}
