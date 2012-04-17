<?php

use Social\Facebook;

require_once __DIR__ . '/../include.php';

if (!empty($_GET['logout'])) {
    unset($_SESSION['facebook']);
    header('Location: http://' . $_SERVER['HTTP_HOST'] . preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']));
    exit();
}

$facebook = new Facebook\Connection($cfg->facebook['appid'], $cfg->facebook['secret'], isset($_SESSION['facebook']) ? $_SESSION['facebook'] : null);

if (!empty($_GET['facebook_login'])) {
    $url = $facebook->getAuthUrl(array('user_hometown', 'user_events'));
    header("Location: $url");
    exit();
}

if (!empty($_GET['facebook_auth'])) {
    $_SESSION['facebook'] = $facebook->handleAuthResponse();
}

if (!$facebook->isAuth()) {
    echo "<a href='?facebook_login=1'>Login with facebook</a>";
    exit();
}

$me = $facebook->me();
?>

<div><a href="?logout=1">Logout</a></div>

<img src="picture.php" />
<h1>Hi <?= $me->first_name; ?>,</h1>

<h2><?= $me->hometown->name ?></h2>

<!-- Auto expand hometown -->
<?= $me->hometown->description ?>
<div><a href="<?= $me->hometown->link ?>">View on Facebook</a></div>

<!-- Show friends -->
<?php $i=0; ?>
<ul>
<?php foreach($me->friends as $friend) : ?>
  <?php if ($i++ >= 30) break; ?>
  <li><?= $friend->name ?></li>
<?php endforeach;?>
</ul>

<!-- Load events -->
<?php
    $me->fetch('events', array('since'=>time()));
    $i = 0;
?>
<ul>
<?php foreach ($me->events as $event) : ?>
  <?php if ($i++ >= 30) break; ?>
  <li><?= $event->name ?></li>
<?php endforeach; ?>
</ul>