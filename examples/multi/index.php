<?php

use Social\Google;
use Social\PostcodeAPI;
use Social\SoundCloud;

require_once '../include.php';

$google = new Google\Connection($cfg->google->api_key, $cfg->google->client_id, $cfg->google->client_secret);
$postcodeapi = new PostcodeAPI\Connection($cfg->postcode_api->api_key);
$soundcloud = new SoundCloud\Connection($cfg->soundcloud->client_id, $cfg->soundcloud->client_secret);

echo "<h1>Execute multiple requests in parallel</h1>";

// Single
$time = microtime(true);

$google->get('webfonts/v1/webfonts', ['fields'=>'items/family']);
$postcodeapi->get(['1015AG', '1012VK', '5041EB', '2021EE']);
$soundcloud->get('tracks/13158665');
$soundcloud->get('apps/124');

echo "<h2>Single: " . (microtime(true) - $time) . "</h2>";

// Multi
$time = microtime(true);

$results = Social\Connection::execAll(
    $google->prepare()->get('webfonts/v1/webfonts', ['fields'=>'items/family']),
    $postcodeapi->prepare()->get(['1015AG', '1012VK', '5041EB', '2021EE']),
    $soundcloud->prepare()->get('tracks/13158665')->get('apps/124')
);

echo "<h2>Parallel: " . (microtime(true) - $time) . "</h2>";

var_dump($results[$google][0]);
var_dump($results[$postcodeapi]);
var_dump($results[$soundcloud]);
