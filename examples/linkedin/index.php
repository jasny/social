<?php

use Social\LinkedIn;

require_once '../include.php';

$linkedin = new LinkedIn\Connection($cfg->lindedin->client_id, $cfg->linkedin->client_secret, $_SESSION);
$linkedin->auth(['r_basicprofile']);

var_dump($linkedin->me());
