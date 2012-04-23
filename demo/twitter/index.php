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
        case 'auth':
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
?>

<div><a href="?logout=1">Logout</a></div>

<h1>Hi, <?= $me->name ?></h1>
