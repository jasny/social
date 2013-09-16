<?php

use Social\PostcodeAPI;

require_once '../include.php';

$postcodeapi = new PostcodeAPI\Connection($cfg->postcode_api->api_key);

var_dump($postcodeapi->get('1015AG/146'));
var_dump($postcodeapi->get(['1015AG', '1012VK', '5041EB', '2021EE']));
