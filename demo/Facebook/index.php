<?php

use Social\Facebook;

require_once __DIR__ . '/../include.php';

if (!empty($_GET['logout'])) {
    unset($_SESSION['fb']);
    header('Location: http://' . $_SERVER['HTTP_HOST'] . preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']));
    exit();
}

$facebook = new Facebook\Connection($cfg->fb['appid'], $cfg->fb['secret'], isset($_SESSION['fb']) ? $_SESSION['fb'] : null);

if (isset($_GET['code'])) {
    $_SESSION['fb'] = $facebook->handleAuthResponse();
}

if (!$facebook->isAuth()) {
    $url = $facebook->getAuthUrl(array('user_hometown', 'user_events'));
    echo "<a href='$url'>$url</a>";
    exit();
}

if ($facebook->isExpired(24 * 3600)) $_SESSION['fb'] = $facebook->extendAccess();

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