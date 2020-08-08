<?php

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT."/vendor/autoload.php";

$app = new App\Application();
$app->run();
