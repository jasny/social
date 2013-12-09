<?php

set_include_path(dirname(__DIR__) . '/src/' . DIRECTORY_SEPARATOR . get_include_path());

$loader = require_once(dirname(__DIR__) . "/vendor/autoload.php");
$loader->setUseIncludePath(true);

session_start();

if (!file_exists(__DIR__ . '/config.php')) {
    echo "Please configure the demo. Copy config.orig.php to config.php and fill in the settings.";
    exit();
}
require_once __DIR__ . '/config.php'; // Excluded from GIT
