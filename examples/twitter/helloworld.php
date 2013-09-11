<?php
use Social\Twitter;

require_once __DIR__ . '/../include.php';

$twitter = new Twitter\Connection($cfg->twitter->consumer_key, $cfg->twitter->consumer_secret, $_SESSION);
$twitter->auth();

echo "<h1>Hi ", $twitter->me()->name, "</h1>";

var_dump($twitter->me()->name);

$twitter->me()->tweet("Hello social world!");
