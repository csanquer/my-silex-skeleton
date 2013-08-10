<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

use CSanquer\Silex\Tools\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArgvInput;


$console = new Application(__DIR__.'/..', 'My Silex Application', 'n/a', 'app');
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->getDefinition()->addOption(new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'disabling debug'));

// register commands to the application
//$console
//    ->register('my-command')
//    ->setDefinition(array(
//        // new InputOption('some-option', null, InputOption::VALUE_NONE, 'Some help'),
//    ))
//    ->setDescription('My command description')
//    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
//        // do something
//    })
//;

// or add your existing commands to the application
//$console->add(new MyCommand());
   
$console->add(new CSanquer\Silex\Tools\Command\CacheClearCommand());

return $console;
