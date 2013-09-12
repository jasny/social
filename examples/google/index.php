<?php

use Social\Google;

require_once '../include.php';

$google = new Google\Connection($cfg->google->api_key, $cfg->google->client_id, $cfg->google->client_secret, $_SESSION);
$google->auth();

var_dump($google->me());
var_dump($google->get('webfonts/v1/webfonts', ['fields'=>'items/family']));
var_dump($google->api('books', null, 'key')->get('volumes', ['q'=>'Harry Potter']));
