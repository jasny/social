<?php

// Example cron script to extend user access (run daily)

use Social\Facebook;

require_once __DIR__ . '/../include.php';

$facebook = new Facebook\Connection($cfg->facebook['appid'], $cfg->facebook['secret']);

$result = $db->query("SELECT id, username, facebook_token, facebook_expires FROM user WHERE facebook_expires < NOW() + INTERVAL 2 DAY"); // Get all users for which the facebook token will expire within 2 days

while ($user = $result->fetch_object()) {
    try {
        $access = $facebook->asUser($user->facebook_token, $user->facebook_expires)->extendAccess();
        $values[] = "({$user->id}, '{$access->token}', {$access->expires})";
    } catch (Social\Exception $e) {
        fwrite(STDERR, "Failed to extend Facebook access for user '{$user->username}': " . $e->getMessage());
    }
}

if (!empty($values)) {
    $query = "INSERT INTO user (id, facebook_token, facebook_expires) VALUES " . join(', ', $values) .
      " ON DUPLICATE KEY UPDATE facebook_token = VALUES(facebook_token), facebook_expires=VALUES(facebook_expires)";
    $db->query($query);
}