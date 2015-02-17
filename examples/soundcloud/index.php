<?php

use Social\SoundCloud;

require_once '../include.php';

$soundcloud = new SoundCloud\Connection($cfg->soundcloud->client_id, $cfg->soundcloud->client_secret, $_SESSION);
$soundcloud->auth();

var_dump($soundcloud->me());
var_dump($soundcloud->get('tracks/13158665'));
var_dump($soundcloud->get('apps/124'));
var_dump($soundcloud->resolve("https://soundcloud.com/mayerhawthorne/her-favorite-song-oliver-remix"));
