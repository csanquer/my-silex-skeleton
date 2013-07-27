<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArgvInput;

class Application extends BaseApplication
{
    protected $projectDir;

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);
        $this->projectDir = realpath(__DIR__.DS.'..');
    }

    public function getProjectDir()
    {
        return $this->projectDir;
    }

    public function getBinDir()
    {
        return $this->projectDir.DS.'bin';
    }
    
    public function getAppDir()
    {
        return $this->projectDir.DS.'app';
    }
    
    public function getConfigDir()
    {
        return $this->getAppDir().DS.'config';
    }
    
    public function getWebDir()
    {
        return $this->projectDir.DS.'web';
    }
}

$console = new Application('My Silex Application', 'n/a');
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
//$application->add(new MyCommand());
    
return $console;
