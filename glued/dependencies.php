<?php
// DIC configuration


// get the DI container, bind all dependencies to it (auth, view, db, etc.)
$container = $app->getContainer();


// CLASSES


// glued core class
$container['core'] = function ($container) {
    return new \Glued\Classes\Core\Core($container);
};

// glued authentication class
$container['auth'] = function ($container) {
    return new \Glued\Classes\Auth\Auth($container);
};


// glued Permissions class (acl, rbacs, abac)
$container['permissions'] = function ($container) {
    return new \Glued\Classes\Permissions\Permissions($container);
};


// tags class
$container['tags'] = function ($container) {
    return new \Glued\Classes\Tags\Tags($container);
};


// stor class
$container['stor'] = function ($container) {
    return new \Glued\Classes\Stor\Stor($container);
};


// glued class about logged user
$container['auth_user'] = function ($container) {
    return new \Glued\Classes\Auth_user\Auth_user($container);
};


// view renderer using the twig template engine
$container['view'] = function ($container) {

    // define $view, set the path to the twig templates, turn off caching for development
    $view = new \Slim\Views\Twig(__DIR__ . '/Views', [
        'cache' => false,
    ]);

    // allow to generate different urls to our views
    $view->addExtension(new \Slim\Views\TwigExtension(
        // passing our router here as we'll be
        // generating urls for links in twig views
        $container->router,
        $container->request->getUri()
    ));

    // this is here so that we can use (i.e. see views/templates/partials/navigation.twig)
    // {{ auth.check }}, as set in classes/Auth/Auth.php, inside our templates.
    // NOTE: $container['auth'] closure must be before this view closure. (KUBA: nemusi, vola se az pri prvnim pouziti view, a to je az po definici vsech closure)
    // NOTE: we cant use $view->getEnvironment()->addGlobal('auth', $container->auth); 
    //       as this would do a sql query everytime we access the global
    // TODO: possibly change this into middleware later?
    // KUBA: upraveno na promenne objektu auth_user, ktere se nacitaji jen jednou v ramci zpracovani stranky
    $view->getEnvironment()->addGlobal('auth', [
        'check' => $container->auth_user->check,
        'user' => $container->auth_user->user,
        'email' => $container->auth_user->email,
        'root' => $container->auth_user->root
    ]);
    
    $basePath = rtrim(str_ireplace("index.php", "", $container["request"]->getUri()->getBasePath()), "/");
    
    $view->getEnvironment()->addGlobal('flash', $container->flash);
    $view->getEnvironment()->addGlobal('public_path', $basePath);
    
    return $view;
};


// database (pure mysqli)
$container['mysqli'] = function ($container) {
    $db = $container['settings']['db'];
    $mysqli = new mysqli($db['host'], $db['username'], $db['password'], $db['database']);
    $mysqli->set_charset($db['charset']);
    $mysqli->query("SET collation_connection = ".$db['collation']);
    return $mysqli;
};


// database (joshcam/PHP-MySQLi-Database-Class)
$container['db'] = function ($container) {
    $mysqli = $container->get('mysqli');
    $db = new \MysqliDb ($mysqli);
    return $db;
};


// flash messages
$container['flash'] = function ($container) {
    return new \Slim\Flash\Messages();
};


// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};


// glued validation class
$container['validator'] = function ($container) {
   return new Glued\Classes\Validation\Validator;
};


// CONTROLERS


// glued home view
$container['HomeController'] = function ($container) {
    return new \Glued\Controllers\HomeController($container);
};


// glued authentication
$container['AuthController'] = function ($container) {
    return new \Glued\Controllers\Auth\AuthController($container);
};

// glued authentication controler, api
$container['AuthControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Auth\AuthControllerApiV1($container);
};

// glued Permissions controler, html output
$container['PermissionsController'] = function ($container) {
    return new \Glued\Controllers\Permissions\PermissionsController($container);
};

// glued Permissions controler, api
$container['PermissionsControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Permissions\PermissionsControllerApiV1($container);
};

// glued file upload
$container['UploadController'] = function ($container) {
    return new \Glued\Controllers\UploadController($container);
};


// time controller api
$container['TimeController'] = function ($container) {
    return new \Glued\Controllers\Api\v0_1\TimePixelsController($container);
};


$container['csrf'] = function ($container) {
    return new \Slim\Csrf\Guard;
};

// glued accounting module, html output controler
$container['AccountingCostsController'] = function ($container) {
    return new \Glued\Controllers\Accounting\AccountingCostsController($container);
};

// glued accounting module, api version controler
$container['AccountingCostsControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Accounting\AccountingCostsControllerApiV1($container);
};

// stor controler
$container['StorController'] = function ($container) {
    return new \Glued\Controllers\Stor\StorController($container);
};

// stor controler api
$container['StorControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Stor\StorControllerApiV1($container);
};

// stock controler
$container['StockController'] = function ($container) {
    return new \Glued\Controllers\Assets\StockController($container);
};

// stock controler api
$container['StockControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Assets\StockControllerApiV1($container);
};

// barcode controler
$container['BarcodeController'] = function ($container) {
    return new \Glued\Controllers\Barcode\BarcodeController($container);
};

// consumables controler
$container['ConsumablesController'] = function ($container) {
    return new \Glued\Controllers\Consumables\ConsumablesController($container);
};

// consumables controler api
$container['ConsumablesControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Consumables\ConsumablesControllerApiV1($container);
};


// parts controler
$container['PartsController'] = function ($container) {
    return new \Glued\Controllers\Parts\PartsController($container);
};

// parts controler api
$container['PartsControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Parts\PartsControllerApiV1($container);
};

// fbevents controller
$container['FBEventsController'] = function ($container) {
    return new \Glued\Controllers\FBEvents\FBEventsController($container);
};

// fbevents controler, api
$container['FBEventsControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\FBEvents\FBEventsControllerApiV1($container);
};

// glued events controler
$container['GEventsController'] = function ($container) {
    return new \Glued\Controllers\GEvents\GEventsController($container);
};

// vectors controler
$container['VectorsController'] = function ($container) {
    return new \Glued\Controllers\Vectors\VectorsController($container);
};

// vectors controler, api
$container['VectorsControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Vectors\VectorsControllerApiV1($container);
};

// wiki controller
$container['WikiController'] = function ($container) {
    return new \Glued\Controllers\Wiki\WikiController($container);
};

// glued contacts module, html output controler
$container['ContactsController'] = function ($container) {
    return new \Glued\Controllers\Contacts\ContactsController($container);
};

// glued contacts module, api version controler
$container['ContactsControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Contacts\ContactsControllerApiV1($container);
};

// import data controller
$container['ImportDataController'] = function ($container) {
    return new \Glued\Controllers\ImportData\ImportDataController($container);
};


