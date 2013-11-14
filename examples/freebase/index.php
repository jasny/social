<?php

use Social\Freebase;

require_once '../include.php';

$freebase = new Freebase\Connection();

$time = microtime(true);

var_dump($freebase->search("Obama", ['limit'=>3]));

$result = $freebase->search(["Obama", "Clinton", "Bush"], ['limit'=>3]);
var_dump($result[0]->result, $result[1]->result, $result[2]->result);

var_dump($freebase->filter("(all type:film /film/film/directed_by:Ridley+Scott)", ['limit'=>5]));

echo "<h1>" . (microtime(true) - $time) . "</h1>";

// Multi

$time = microtime(true);

$results = $freebase->prepare()
    ->search("Obama", ['limit'=>3])
    ->search(["Obama", "Clinton", "Bush"], ['limit'=>3])
    ->filter("(all type:film /film/film/directed_by:Ridley+Scott)", ['limit'=>5])
    ->execute();

var_dump($results);

var_dump($results[0]);
var_dump($results[1][0]->result, $results[1][1]->result, $results[1][2]->result);
var_dump($results[2]);

echo "<h1>" . (microtime(true) - $time) . "</h1>";
