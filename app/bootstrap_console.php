<?php

use CSanquer\Silex\Tools\ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArgvInput;

$console = new ConsoleApplication($app, 'Silex Application', 'n/a');

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
$console->add(new CSanquer\Silex\Tools\Command\AsseticDumpCommand());

return $console;
