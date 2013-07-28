<?php

use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use Symfony\Component\ClassLoader\DebugClassLoader;
use Symfony\Component\CssSelector\Exception\ParseException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Debug\ErrorHandler;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler;
use Symfony\Component\Yaml\Yaml;

// get environment constants or set default
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('SILEX_ENV')) {
    define('SILEX_ENV', 'dev');
}

if (!defined('SILEX_DEBUG')) {
    define('SILEX_DEBUG', true);
}

//Debugging
if (SILEX_DEBUG) {
    error_reporting(-1);
//    error_reporting(E_ALL & E_STRICT);
    DebugClassLoader::enable();
    ErrorHandler::register();
    if ('cli' !== php_sapi_name()) {
        ExceptionHandler::register();
    }
} else {
    ini_set('display_errors', 0);
}

/**
 * @var Application new Silex application
 */
$app = new Application();

$fs = new Filesystem();

// define default file write mode
$app['umask'] = 0002;
$app['file_mode'] = 0775;
umask($app['umask']);

// define environment variables
$app['debug'] = (bool) SILEX_DEBUG;
$app['env'] = SILEX_ENV;

// define main paths
$app['app_dir'] = realpath(__DIR__);
$app['root_dir'] = realpath($app['app_dir'].DS.'..');
$app['web_dir'] = $app['root_dir'].DS.'web';
$app['log_dir'] = $app['app_dir'].DS.'logs';
$app['cache_dir'] = $app['app_dir'].DS.'cache';

//create cache directories
$cacheDirectories = array(
    $app['cache_dir'],
    $app['cache_dir'].DS.'config',
    $app['cache_dir'].DS.'http',
    $app['cache_dir'].DS.'twig',
    $app['cache_dir'].DS.'profiler',
);

if (!$fs->exists($cacheDirectories)) {
    $fs->mkdir($cacheDirectories, $app['file_mode']);
}

foreach ($cacheDirectories as $dir) {
    $fs->chmod($dir, $app['file_mode']);
}

// get configs
$configFiles = array(
    'config',
    'config_'.$app['env'],
);

$configFormats = array(
    'php',
//    'ini',
//    'json',
    'yml',
//    'xml',
);

$app->register(
    new \Herrera\Wise\WiseServiceProvider(),
    array(
        'wise.path' => $app['app_dir'].DS.'config',
        'wise.cache_dir' => $app['cache_dir'].DS.'config',
        'wise.options' => array(
            'parameters' => $app
        )
    )
);

$config = array();
foreach ($configFiles as $configFile) {
    $conf = array();
    foreach ($configFormats as $configFormat) {
        if ($fs->exists($app['app_dir'].DS.'config'.DS.$configFile.'.'.$configFormat)) {
            $conf = $app['wise']->load($configFile.'.'.$configFormat);
        }
    }
    
    if (!empty($conf)) {
        $config = array_replace_recursive($config, $conf);
    }
}

/*
 * add service providers
 */

//add http cache
//$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
//    'http_cache.cache_dir' => $app['cache_dir'].DS.'http',
//));

//add url generator
$app->register(new UrlGeneratorServiceProvider());

//add symfony2 sessions
$app->register(new SessionServiceProvider());

//add symfony2 forms and validators
$app->register(new ValidatorServiceProvider());

//add service controller provider
$app->register(new ServiceControllerServiceProvider());

//symfony2 form provider, must be registered before twig
$app->register(new FormServiceProvider(), array(
    'form.secret' => '4fws6dg4w6df4<qg4sh4646qfgsd4',
));

//add symfony2 translation (needed for twig + forms)
$app->register(new TranslationServiceProvider(), array(
    'locale_fallback' => empty($config['locale_fallback']) ? 'en' : $config['locale_fallback'],
));

// add twig templating
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
//        'twig.templates' => array(),
    'twig.options' => array(
        'debug' => $app['debug'],
        'cache' => $app['cache_dir'].DS.'twig',
        'auto_reload' => $app['debug'],
    ),
    'twig.form.templates' => array(
        'form_div_layout.html.twig', // Twig SF2 original form theme
        // add your custom form theme
    ),
));

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) use ($config) {
    // add custom globals, filters, tags, ...
    if (!empty($config['twig']['variables'])) {
        foreach ($config['twig']['variables'] as $name => $value) {
            $twig->addGlobal($name, $value);
        }
    }
    
    return $twig;
}));

//add swiftmailer 
if (!empty($config['swiftmailer'])) {
    $app->register(new SwiftmailerServiceProvider(), array(
        'swiftmailer.options' => isset($config['swiftmailer']['options']) ? $config['swiftmailer']['options'] : array(),
    ));
    // custom swiftmailer transport
    $swiftTransport = in_array($config['swiftmailer']['transport'], array('mail', 'sendmail', 'smtp')) ? $config['swiftmailer']['transport'] : 'smtp';
    switch ($swiftTransport) {
        case 'mail':
            $app['swiftmailer.transport'] = new \Swift_MailTransport();
            break;
        case 'sendmail':
            $app['swiftmailer.transport'] = new \Swift_SendmailTransport();
            break;
        case 'smtp':
        default:
            break;
    }
}

//Database Doctrine DBAL Connection
if (!empty($config['doctrine'])) {
    $app->register(new DoctrineServiceProvider(), array(
        'db.options' => is_array($config['doctrine']) ? $config['doctrine'] : array(),
    ));
}

if ('dev' == SILEX_ENV) {
    // logs
    $app->register(new MonologServiceProvider(), array(
        'monolog.logfile' => $app['log_dir'].DS.'silex_dev.log',
    ));

    // symfony2 web profier
    $app->register($p = new WebProfilerServiceProvider(), array(
        'profiler.cache_dir' => $app['cache_dir'].DS.'profiler',
    ));

    $app->mount('/_profiler', $p);
}

return $app;
