<?php

use Social\Twitter;

require_once __DIR__ . '/../include.php';

if (!empty($_GET['logout'])) {
    unset($_SESSION['twitter']);
    header('Location: http://' . $_SERVER['HTTP_HOST'] . preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']));
    exit();
}

$twitter = new Twitter\Connection($cfg->twitter['consumer_key'], $cfg->twitter['consumer_secret'], isset($_SESSION['twitter']) ? $_SESSION['twitter'] : null);

if (!empty($_GET['twitter_auth'])) {
    $_SESSION['twitter'] = $twitter->handleAuthResponse();
    $db->query("UPDATE user SET token=?, secret=?", $twitter->getAccessToken(), $twitter->getAccessSecret());
}

if (!$twitter->isAuth()) {
    $url = $twitter->getAuthUrl();
    echo "<a href='$url'>$url</a>";
    exit();
}

//$me = $twitter->me();
?>

<div><a href="?logout=1">Logout</a></div>

<h1>Logged in</h1>