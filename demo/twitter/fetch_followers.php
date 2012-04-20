<?php

/**
 * Basic script to fetch all followers for up to 1000 twitter users taken from the DB.
 * The script would need a few adjustments if you want to run multiple scripts (on multiple servers) in parallel.
 * 
 * This is just an example.
 * Uses Jasny's DB class. @see http://www.github.com/jasny/DB
 */

$twitter = new Twitter($cfg->twitter['consumer_key'], $cfg->twitter['consumer_secret']);

// Get 1000 users from the queue
$now = time();
$users = DB::conn()->query("SELECT twitter_id AS id, twitter_token AS token, twitter_secret AS secret FROM user INNER JOIN queue_twitter_followers AS queue ON user.id = queue.user_id ORDER BY queue.timestamp ASC LIMIT 1000")->fetch_all(MYSQLI_ASSOC);

// Fetch the followers for all these users (up to 1000 parallel requests)
$user_followers = $twitter->collection('me', $users)->fetchAll('followers');

// Build up values for update query
foreach ($user_followers as $user) {
    foreach ($user->followers as $follower) {
        $followers[] = array('source_twitter_id' => $user->id, 'follower_twitter_id' => $follower->id, 'timestamp' => $now);
    }
    if ($user->followers->isLoaded()) $user_ids[] = $user->id; // We got all followers for this user (it's not Lady Gaga)
}

if (!empty($followers)) {
    // Add / touch followers (1 query per 1000 rows)
    foreach (array_chunk($followers, 1000) as $chunk) {
        DB::conn()->save('twitter_follower', $chunk);
    }
    
    // Delete all followers that are not touched
    DB::conn()->query("DELECT FROM twitter_follower WHERE timestamp != ? AND source_twitter_id IN ?", $now, $user_ids);
}


// Alternatively if you would use a random user to fetch followers from others
/*
$twitter = new Twitter($cfg->twitter['consumer_key'], $cfg->twitter['consumer_secret'], $user_token, $user_secret);
$users = DB::conn()->query("SELECT twitter_id AS id FROM user INNER JOIN queue_twitter_followers AS queue ON user.id = queue.user_id ORDER BY queue.timestamp ASC LIMIT 1000")->fetch_all(MYSQLI_ASSOC);
$user_followers = $twitter->collection('user', $users)->fetchAll('followers');
*/