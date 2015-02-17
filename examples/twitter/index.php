<?php
use Social\Twitter;

require_once __DIR__ . '/../include.php';

$twitter = new Twitter\Connection($cfg->twitter->consumer_key, $cfg->twitter->consumer_secret, $_SESSION);
$twitter->auth();

if (isset($_POST['tweet'])) {
    $twitter->post('statuses/update', ['status'=>$_POST['tweet']]);
    $success = "The tweet has been posted";
}

$me = $twitter->me();

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

        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" />
    </head>
    <body style="padding-top: 60px">
        <div class="navbar navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container">
                    <span class="brand">Jasny Social demo</span>
                        <ul class="nav nav-secondary pull-right">
                            <li><a href="?logout=1">Logout</a></li>
                        </ul>
                </div>
            </div>
        </div>

        <div class="container">
            <img src="<?= $me->profile_image_url ?>" class="pull-right" />
            <h1>Hi <?= $me->name ?>,</h1>
            <div><a href="http://twitter.com/<?= $me->screen_name ?>">@<?= $me->screen_name ?></a></div>

            <hr>
            <h3>Send a tweet</h3>

            <?php if (isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif ?>

            <form method="post" action="index.php">
                <fieldset><textarea name="tweet" class="span8" rows="5"></textarea></fieldset>
                <button class="btn">Tweet</button>
            </form>
        </div>
        
        <pre>
            <?php var_dump($twitter->get('statuses/user_timeline')); ?>
        </pre>
    </body>
</html>
