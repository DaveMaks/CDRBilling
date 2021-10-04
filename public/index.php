<?php
use Phalcon\Escaper;
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Session\Manager as Session;
use Phalcon\Url as UrlProvider;
use Phalcon\Config;
use Phalcon\Flash\Session as Flash;

error_reporting(E_ALL);
ini_set('display_errors', 1);
//phpinfo();
// Определяем некоторые константы с абсолютными путями
// для использования с локальными ресурасами
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
include_once APP_PATH . '/config/defines.php';
include_once APP_PATH . '/plugins/functions.php';

// Регистрируем автозагрузчик
$loader = new Loader();

$loader->registerDirs(
    [
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
        APP_PATH . '/plugins/',
    ]
);
$loader->registerNamespaces(
    [
        'App\LocalClass' => APP_PATH . '/models',
        'App\HTML' => APP_PATH . '/plugins',
        'App\Plugin' => APP_PATH . '/plugins',
        'App\Plugin\Menu' => APP_PATH . '/plugins/Menu',
        'App' => APP_PATH . '/plugins',
        'PhpOffice\PhpSpreadsheet' => APP_PATH . '/plugins/PhpSpreadsheet',
        'ZipStream' => APP_PATH . '/plugins/ZipStream',
        'MyCLabs\Enum' => APP_PATH . '/plugins/MyCLabs',
        'App\PBX' => APP_PATH . '/plugins/ParsingPBXBilling',
        'App\Validator' => APP_PATH . '/plugins',

    ]
);
$loader->register();

// Создаём контейнер DI
$di = new FactoryDefault();

$di->set(
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
$di->setShared(
    'session',
    function () use ($di) {
        $session = new Session();
        $files = new Phalcon\Session\Adapter\Stream([
            'savePath' => $this->get('config')->tempDir
        ]);
        $session->setAdapter($files)->start();
        $session->start();
        return $session;
    }
);

// астраиваем компонент представлений
$di->set(
    'view',
    function () {
        $view = new View();
        $view->setViewsDir(APP_PATH . '/views/');
        return $view;
    }
);
// Setup a base URI
$di->set(
    'url',
    function () {
        $url = new UrlProvider();
        $url->setBaseUri($this->get('config')->baseUrl);
        return $url;
    });
$di->set(
    'db',
    function () {
        return new DbAdapter(
            require APP_PATH . '/config/db.php'
        );
    }
);


$di->set(
    'flash',
    function () {
        $escaper = new Escaper();
        $flash = new Flash($escaper);
        $flash->setImplicitFlush(true);
        $flash->setCssClasses([
            'error' => 'alert alert-danger',
            'success' => 'alert alert-success',
            'notice' => 'alert alert-info',
            'warning' => 'alert alert-warning'
        ]);
        return $flash;
    }
);
/*
$di->set('acl', function () {
    return new App\Plugin\AclService();
});*/

$di->set('dispatcher', function () use ($di) {
    $eventsManager = $di->getShared('eventsManager');
    /*$eventsManager->attach(
        "dispatch:beforeException",
        function ($event, $dispatcher, $exception) {

        }
    );*/
// создаем экземпляр плагина безопасности
    $security = new SecurityPlugin($di);
// прослушка для событий созданных в диспетчере, используя плагин безопасности
    $eventsManager->attach('dispatch', $security);
    $dispatcher = new Dispatcher();
// связываем EventsManager с Dispatcher
    $dispatcher->setEventsManager($eventsManager);
    return $dispatcher;
});


$application = new Application($di);

try {
    date_default_timezone_set($application->config->TimeZone);
    // Handle the request
    $request = new Phalcon\Http\Request();
    $response = $application->handle($request->getURI());
    $response->send();
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
    echo $e->getTraceAsString();
}