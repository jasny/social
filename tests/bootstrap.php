<?php

set_include_path(dirname(__DIR__) . '/src:' . get_include_path());

function loadClass($name)
{
    $filename = strtr($name, '\\_', '//') . ".php";
    if (@fopen($filename, 'r', true)) require_once $filename;
}

spl_autoload_register('loadClass');

require_once __DIR__ . '/config.php'; // Excluded from GIT
