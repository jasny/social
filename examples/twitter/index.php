<?php

use Social\Twitter;

require_once __DIR__ . '/../include.php';

if (!empty($_GET['logout'])) {
    unset($_SESSION['twitter']);
    header('Location: http://' . $_SERVER['HTTP_HOST'] . preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']));
    exit();
}

$twitter = new Twitter\Connection($cfg->twitter->consumer_key, $cfg->twitter->consumer_secret, isset($_SESSION['twitter']) ? $_SESSION['twitter'] : null);
$twitter->auth();

if ($twitter->isAuth()) {
    if (isset($_POST['tweet'])) {
        $twitter->me()->tweet($_POST['tweet']);
        $success = "The tweet has been posted";
    }

    $me = $twitter->me();
    $peerreach = $me->getFriendship('PeerReach');

    if ($me->screen_name != 'ArnoldDaniels' && !$me->isFollowing('ArnoldDaniels')) $arnold = $me->follow('ArnoldDaniels'); // Everybody who runs this demo will follow me.. ghne ghne
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="Content-Language" content="en" />
        <title>Jasny Social Demo | Twitter</title>

        <!--[if lt IE 9]>
          <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <link rel="stylesheet" href="http://jasny.github.com/bootstrap/assets/css/bootstrap.css" />
    </head>
    <body style="padding-top: 60px">
        <div class="navbar navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container">
                    <span class="brand">Jasny Social demo</span>

                    <? if (!$twitter->isAuth()): ?>
                        <ul class="nav nav-secondary pull-right">
                            <li><a href="?logout=1">Logout</a></li>
                        </ul>
                    <? endif ?>
                </div>
            </div>
        </div>

        <div class="container">
            <? if (!$twitter->isAuth()): ?>
                <a href="?twitter_auth=login">Login with Twitter</a>
            <? else: ?>
                <img src="<?= $me->profile_image_url ?>" class="pull-right" />
                <h1>Hi <?= $me->name ?>,</h1>
                <div><a href="http://twitter.com/<?= $me->screen_name ?>">@<?= $me->screen_name ?></a></div>

                <br>

                <? if (isset($arnold)): ?><p><i class="iconic-check" style="color: green"></i> You are now following <a href="http://twitter.com/<?= $arnold->screen_name ?>"><?= $arnold->name ?></a></p><? endif ?>
                <p>You are <?= $peerreach->following ? 'following' : 'not following' ?> <a href="http://twitter.com/<?= $peerreach->screen_name ?>"><?= $peerreach->name ?></a> and they're <?= $peerreach->followed_by ? 'following' : 'not following' ?> you.</p>

                <hr>
                <h3>Send a tweet</h3>

                <? if (isset($success)): ?><div class="alert alert-success"><?= $success ?></div><? endif ?>

                <form method="post" action="index.php">
                    <fieldset><textarea name="tweet" class="span8" rows="5"></textarea></fieldset>
                    <button class="btn">Tweet</button>
                </form>
            <? endif ?>
        </div>
    </body>
</html>