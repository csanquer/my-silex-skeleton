<?php

// get environment constants or set default
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once __DIR__.DS.'..'.DS.'vendor'.DS.'autoload.php';

/**
 * @var \Silex\Application Silex application
 */
$app = require_once __DIR__.DS.'bootstrap_app.php';

// customize or add silex providers

// mount application controllers 
require __DIR__.DS.'controllers.php';

//run silex application
$app->run();
