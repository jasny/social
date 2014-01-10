<?php

use Social\LinkedIn;

require_once '../include.php';

$linkedin = new LinkedIn\Connection($cfg->linkedin->client_id, $cfg->linkedin->client_secret, $_SESSION);
$linkedin->auth(['r_basicprofile']);

echo "<img src='" . $linkedin->me()->getPicture() . "'>";

var_dump($linkedin->me());
