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

/*
 * define environment variables
 */
$app['debug'] = (bool) SILEX_DEBUG;
$app['env'] = SILEX_ENV;

/*
 * define main paths
 */
$app['app_dir'] = realpath(__DIR__);
$app['root_dir'] = realpath($app['app_dir'].DS.'..');
$app['web_dir'] = $app['root_dir'].DS.'web';
$app['log_dir'] = $app['app_dir'].DS.'logs';
$app['cache_dir'] = $app['app_dir'].DS.'cache';

// get configs
// TODO refactoring
$fs = new Filesystem();

$configFiles = array(
    'config',
    'config_'.$app['env'],
);

$config = array();
foreach ($configFiles as $configFile) {
    $conf = array();
    if (!$fs->exists($app['cache_dir'].DS.'config'.DS.$configFile.'.php')) {
        if ($fs->exists($app['app_dir'].DS.'config'.DS.$configFile.'.php')) {
            $conf = require_once $app['app_dir'].DS.'config'.DS.$configFile.'.php';
            $rights = isset($conf['cache_access']) && is_int($conf['cache_access']) ? $conf['cache_access'] : 0775;
            
            $fs->copy(
                $app['app_dir'].DS.'config'.DS.$configFile.'.php', 
                $app['cache_dir'].DS.'config'.DS.$configFile.'.php'
            );
            $fs->chmod($app['cache_dir'].DS.'config'.DS.$configFile.'.php', $rights);
            
        } elseif ($fs->exists($app['app_dir'].DS.'config'.DS.$configFile.'.yml')) {
            try {
                $conf = Yaml::parse($app['app_dir'].DS.'config'.DS.$configFile.'.yml');
                $rights = isset($conf['cache_access']) && is_int($conf['cache_access']) ? $conf['cache_access'] : 0775;
                
                $fs->dumpFile($app['cache_dir'].DS.'config'.DS.$configFile.'.php', '<?php
return '.var_export($conf, true).';', $rights);
                
            } catch (ParseException $e) {
                throw new ErrorException(sprintf("Unable to parse the YAML string: %s in config file", $e->getMessage()));
//                exit(1);
            }
        }
    } else {
        $conf = require_once $app['cache_dir'].DS.'config'.DS.$configFile.'.php';
    }
    $config = array_replace_recursive($config, $conf);
}

//set umask
$umask = isset($config['umask']) && is_int($config['umask']) ? $config['umask'] : 0002;
umask($umask);

//create cache directories
$cacheDirectories = array(
    $app['cache_dir'],
    $app['cache_dir'].DS.'config',
    $app['cache_dir'].DS.'http',
    $app['cache_dir'].DS.'twig',
    $app['cache_dir'].DS.'profiler',
);

if (!$fs->exists($cacheDirectories)) {
    $rights = isset($config['cache_access']) && is_int($config['cache_access']) ? $config['cache_access'] : 0775;
    $fs->mkdir($cacheDirectories, $rights);
    foreach ($cacheDirectories as $dir) {
        $fs->chmod($dir, $rights);
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
