<?php

use Social\Facebook;

require_once __DIR__ . '/../include.php';

if (!empty($_GET['logout'])) {
    unset($_SESSION['fb']);
    header('Location: http://' . $_SERVER['HTTP_HOST'] . preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']));
    exit();
}

$facebook = new Facebook\Connection($cfg->fb['appid'], $cfg->fb['secret'], isset($_SESSION['fb']) ? $_SESSION['fb'] : null);

if (isset($_GET['code'])) {
    $facebook->handleAuthResponse();
    $_SESSION['fb'] = $facebook->extendAccess();
}

if (!$facebook->isAuth()) {
    $url = $facebook->getAuthUrl(array('user_hometown', 'user_events'));
    echo "<a href='$url'>$url</a>";
    exit();
}

if ($facebook->isExpired(24 * 3600)) $_SESSION['fb'] = $facebook->extendAccess();

$me = $facebook->me();
?>

<div><a href="?logout=1">Logout</a></div>

<h1>Hi <?= $me->first_name; ?>,</h1>

<h2><?= $me->hometown->name ?></h2>
<!-- Auto expand hometown -->
<?= $me->hometown->description ?>
<div><a href="<?= $me->hometown->link ?>">View on Facebook</a></div>
