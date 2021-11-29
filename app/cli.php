<?php
use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Loader;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Config;
use Phalcon\Config\Adapter\Php;

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
include_once APP_PATH . '/config/defines.php';

// Using the CLI factory default services container
$di = new CliDI();

/**
 * Register the autoloader and tell it to register the tasks directory
 */
$loader = new Loader();
$loader->registerDirs(
    [
        APP_PATH . '/tasks',
        APP_PATH . '/models',
        APP_PATH . '/plugins',
    ]
);
$loader->registerNamespaces(
    [
        'App\LocalClass' => APP_PATH . '/models',
        'App\CDR' => APP_PATH . '/plugins/ParsingCDR',
    ]
);

$loader->register();

$di->set(
    'db',
    function () {
        return new DbAdapter(
            require APP_PATH . '/config/db.php'
        );
    }
);

$di->setShared(
    'config',
    function () {
        $config = [
            'importcdr' => require APP_PATH . '/config/importCDR.php',
            'ActiveDirectory' => require APP_PATH . '/config/ActiveDirectory.php',
        ];
        $config = array_merge($config, require APP_PATH . '/config/ower.php');
        return new Config($config);
    }
);


$console = new ConsoleApp();
$console->setDI($di);
$arguments = [];
foreach ($argv as $k => $arg) {
    if ($k === 1) {
        $arguments['task'] = $arg;
    } elseif ($k === 2) {
        $arguments['action'] = $arg;
    } elseif ($k >= 3) {
        $arguments['params'][] = $arg;
    }
}

try {
// Handle incoming arguments
    $console->handle($arguments);
} catch (Exception $e) {
    echo $e->getTraceAsString();
}