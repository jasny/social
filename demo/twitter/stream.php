<?php

use Social\Twitter;

require_once __DIR__ . '/../include.php';

$twitter = new Twitter\Connection($cfg->twitter['consumer_key'], $cfg->twitter['consumer_secret'], $cfg->twitter['access_token'], $cfg->twitter['access_secret']);

function write($ch, $data)
{
    echo $data, "\n";
    flush();
    return strlen($data);
}

$twitter->stream('write', 'statuses/filter', array('track' => 'nederland'));

echo "Done";
