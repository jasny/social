<?php

use Social\Facebook;

require_once __DIR__ . '/../include.php';

$facebook = new Facebook\Connection($cfg->facebook->appid, $cfg->facebook->secret, $_SESSION);
$facebook->auth(['email', 'user_birthday', 'user_hometown', 'user_likes'])->checkScope();

var_dump($facebook->me());
