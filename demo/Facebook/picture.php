<?php

use Social\Facebook;

require_once __DIR__ . '/../include.php';

$facebook = new Facebook\Connection($cfg->fb['appid'], $cfg->fb['secret'], $_SESSION['fb']);

header('Content-Type: image/jpg');
echo $facebook->fetch('me/picture');