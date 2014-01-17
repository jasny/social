<?php
/**
 * Jasny Social
 * A PHP library for webservice APIs
 * 
 * @license http://www.jasny.net/mit MIT
 * @copyright 2012-2014 Jasny
 */

/** */
namespace Social\Twitter;

/**
 * Entity representing the current authenticated user
 * 
 * @package Twitter
 */
class Me extends User implements \Social\Me
{
    /**
     * Class constructor.
     * 
     * @param object|mixed $data        Data or ID/username; Caution: We don't verify the id/username against the access token.
     * @param boolean      $stub
     * @param Connection   $connection
     */
    public function __construct($data=[], $stub=self::STUB, $connection=null)
    {
        if (!isset($data)) $stub = self::STUB;
        parent::__construct($data, $stub, $connection);
    }
    

    /**
     * Perform an action for a single or multiple items.
     * 
     * @param string $resource
     * @param string $key       Id property or '@user'
     * @param mixed  $entity    Entity/ID or collection/array with entities
     * @param array  $params
     * @param array  $media
     * @return Entity|Collection
     */
    protected function postAll($resource, $key, $entity, array $params=array(), array $media=array())
    {
        $conn = $this->getConnection();
        
        // Single item
        if (!is_array($entity) && !$entity instanceof \ArrayObject) {
            if ($key == '@user') $p = self::makeUserData($entity, true) + $params;
              else $p = ($entity instanceof Entity ? $entity->asParams() : array($key => $entity)) + $params;
            
            if (!empty($media)) $p['media'] = (array)$media;

            return $entity instanceof Entity ?
                $conn->prepare($entity)->post($resource, $p)->execute() :
                $conn->post($resource, $p);
        }
        
        // Multiple items
        $conn->prepare();
        
        foreach ($entity as $i=>$e) {
            if ($key == '@user') $p = self::makeUserData($e, true) + $params;
              else $p = ($e instanceof Entity ? $e->asParams() : array($key => $e)) + $params;
            
            if (!empty($media)) $p['media'] = (array)$media[$i];
            
            if ($e instanceof Entity) $conn->prepare($e)->post($resource, $p)->execute();
             else $conn->post($resource, $p);
        }
        
        return $conn->execute();
    }
    

    /**
     * Fetch all properties of this entity.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/get/account/verify_credentials
     * 
     * @return Me  $this
     */
    public function refresh()
    {
        return $this->getConnection()->prepare($this)->get('account/verify_credentials')->execute();
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
        return $this->getConnection()->prepare($this)->post('account/update_profile', $params)->execute();
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
        $params = is_string($image) ? compact('image') : $image;
        
        return $this->getConnection()
            ->prepare($this)
            ->post('account/update_profile_background_image', $params)
            ->execute();
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
        return $this->getConnection()->prepare($this)->post('account/update_profile_colors', $params)->execute();
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
        $params = is_string($image) ? compact('image') : $image;
        return $this->getConnection()->prepare($this)->post('account/update_profile_image', $params, $this)->execute();
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
        $params = is_string($image) ? compact('image') : $image;
        return $this->getConnection()
            ->prepare($this)
            ->post('account/update_profile_banner', $params, $this)
            ->execute();
    }
    
    
    /**
     * Send a tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/statuses/update
     * @see https://dev.twitter.com/docs/api/1.1/post/statuses/update_with_media
     * 
     * @param string       $message  The status message or an array with messages
     * @param string|array $media    Image(s) as binary content (not link or filename)
     * @param array        $params   Additional parameters
     * @return Tweet|Collection
     */
    public function tweet($message, $media=null, array $params=array())
    {
        return $this->postAll('statuses/update' . (!empty($params['media']) ? '_with_media' : ''), 'status',
            $message, $params, (array)$media);
    }

    /**
     * Retweets a tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/statuses/retweet/%3Aid
     * 
     * @param Tweet|array $tweet   Tweet entity or array with tweets
     * @param array       $params
     * @return Tweet|Collection
     */
    public function retweet($tweet, array $params=array())
    {
        return $this->postAll('statuses/retweet/:id', ':id', $tweet, $params);
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
     * Follow a user/users.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/friendships/create
     * 
     * @param mixed $user  User entity/ID/username or array with users
     * @return User|Collection
     */
    public function follow($user)
    {
        return $this->postAll('friendships/create', '@user', $user);
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
        return $this->postAll('friendships/destroy', '@user', $user);
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
        return $this->postAll('blocks/create', '@user', $user);
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
        return $this->postAll('blocks/destroy', '@user', $user);
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
        return $this->postAll('blocks/update', '@user', $user, $params);
    }
    
    /**
     * Get the relationship between me and the user(s).
     * 
     * Results in value object(s) with the following properties 'following', 'following_requested', 'followed_by'.
     * Optionally also include extra properties: 'notifications_enabled', 'can_dm', 'want_retweets', 'marked_spam',
     *  'all_replies', 'blocking'.
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
        // User::isFollowing() as User::isFollowedBy() uses this method.
        $key = null;
        if ($extended === 'following' || $extended === 'followed_by') {
            $key = $extended;
            $extended = false;
        }
        
        if ($extended) return parent::getFriendship($user);
        
        // Single user
        if (!is_array($user) && !$user instanceof \ArrayObject) {
            return $this->getConnection()->get('friendships/lookup', self::makeUserData($user, true));
        }
        
        // Multiple users (1 request per 100 users)
        $users = $ids = $names = array();
        
        foreach ($user as $u) {
            if (!is_object($u)) $u = new User($this->connection, $u, Entity::STUB);
            
            if (property_exists($u, 'id')) $ids[] = $u->id;
             else $names[] = $u->screen_name;
            
            $users[] = $u;
        }

        $result = new FriendshipResult($this->getConnection(), $users, $key);
        
        $this->getConnection()->prepare($result);
        
        foreach (array_chunk($ids, 100) as $chunk) {
            $this->getConnection()->post('friendships/lookup', array('user_id' => $chunk));
        }
        
        foreach (array_chunk($names, 100) as $chunk) {
            $this->getConnection()->post('friendships/lookup', array('screen_name' => $chunk));
        }
        
        return $this->getConnection()->execute();
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
     * @param Tweet|int|array $tweet   Tweet entity/ID or array of tweets
     * @param array           $params  Additional parameters
     * @return Tweet
     */
    public function favorite($tweet, array $params=array())
    {
        return $this->postAll('favorites/create', 'id', $tweet, $params);
    }

    /**
     * Un-favorite a tweet.
     * 
     * @see https://dev.twitter.com/docs/api/1.1/post/favorites/destroy/%3Aid
     * 
     * @param Tweet|int|array  $tweet   Tweet entity/ID or array of tweets
     * @param array      $params  Additional parameters
     * @return Tweet
     */
    public function unfavorite($tweet, array $params=array())
    {
        return $this->postAll('favorites/destroy', 'id', $tweet, $params);
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
    public function createList($params)
    {
        if (!is_array($params)) $params = array('name' => $params);
        return $this->getConnection()->post('lists/create', $params);
    }
    
    /**
     * Subscribe to a list.
     * 
     * https://dev.twitter.com/docs/api/1.1/post/lists/subscribers/create
     * 
     * @param UserList|int|array $list    UserList entity/ID or array with lists
     * @param array              $params  Additional parameters
     * @return UserList|Collection
     */
    public function subscribe($list, array $params=array())
    {
        return $this->postAll('lists/subscribers/create', 'list_id', $list, $params);
    }

    /**
     * Unsubscribe from a list.
     * 
     * https://dev.twitter.com/docs/api/1.1/post/lists/subscribers/create
     * 
     * @param UserList|int|array $list  UserList entity/ID or array with lists or params
     * @param array              $params  Additional parameters
     * @return UserList|Collection
     */
    public function unsubscribe($list, array $params=array())
    {
        return $this->postAll('lists/subscribers/destroy', 'list_id', $list, $params);
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
        return $this->postAll('saved_searches/create', 'query', $query);
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
     * Sets which device Twitter delivers updates to for the authenticating user.
     * Sending none as the device parameter will disable SMS updates.
     *
     * @see https://dev.twitter.com/docs/api/1.1/post/account/update_delivery_device
     * 
     * @param string $device
     * @return Me  $this
     */
    public function updateDeliveryDevice($device)
    {
        $params = is_array($device) ? $device : compact('device');
        return $this->getConnection()->prepare($this)->post('account/update_delivery_device', $params)->execute();
    }    
}
