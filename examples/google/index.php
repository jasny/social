<?php

use Social\Google;

require_once '../include.php';

$google = new Google\Connection($cfg->google->client_id, $cfg->google->client_secret, $_SESSION);
$google->auth();

var_dump($google->me());
