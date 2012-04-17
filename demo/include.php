<?php

ini_set('display_errors', 1);

set_include_path(dirname(__DIR__) . '/src/:' . get_include_path());

// Autoloader
function loadClass($name)
{
    require_once strtr($name, '\\_', '//') . '.php';
}
spl_autoload_register('loadClass');

session_start();
require_once 'config.php'; // Excluded from GIT

header('Content-type: text/html; charset=utf-8');
