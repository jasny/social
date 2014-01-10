<?php
require_once '../include.php';

$gravatar = new Social\Gravatar\Connection();

var_dump($gravatar->avatarExists('arnold@jasny.net'));
var_dump($gravatar->avatarExists('ab@defdfd.net'));

echo '<img src="' . $gravatar->avatar('arnold@jasny.net', 100) . '">';

var_dump($gravatar->user('arnold@jasny.net'));
