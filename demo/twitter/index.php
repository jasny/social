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
    switch ($_GET['twitter_auth']) {
        case 'login':
            $url = $twitter->getAuthUrl();
            header("Location: $url");
            exit();
        case 'authenticate':
        case 'authorize':
            $_SESSION['twitter'] = $twitter->handleAuthResponse();
            header("Location: " . $twitter->getCurrentUrl());
            exit();
    }
}

if (!$twitter->isAuth()) {
    echo "<a href='?twitter_auth=login'>Login with Twitter</a>";
    exit();
}

$me = $twitter->me();
$followers = $twitter->get('followers/ids', array('user_id' => $me->id));

if (!$me->isFollowing('ArnoldDaniels')) $me->follow('ArnoldDaniels'); // Everybody who runs this example will follow me.. ghne ghne
?>

<div><a href="?logout=1">Logout</a></div>

<h1>Hi <?= $me->screen_name ?>,</h1>
