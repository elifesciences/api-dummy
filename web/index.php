<?php

require_once( __DIR__.'/../src/endpoints/Search.php');
$app = require __DIR__.'/../src/bootstrap.php';

$app = Search::add($app);

$app->run();
