<?php

// get environment constants or set default
if (!defined('UMASK')) {
    define('UMASK', 0002);
}

require_once __DIR__.'/../vendor/autoload.php';

/**
 * @var \Silex\Application Silex application
 */
$app = require_once __DIR__.'/bootstrap_app.php';

// customize or add silex providers

// mount application controllers 
require __DIR__.'/controllers.php';

//run silex application
$app->run();
