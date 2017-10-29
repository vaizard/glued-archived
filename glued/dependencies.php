<?php
// DIC configuration


// get the DI container, bind all dependencies to it (auth, view, db, etc.)
$container = $app->getContainer();


// glued authentication class
$container['auth'] = function ($container) {
    return new \Glued\Classes\Auth\Auth($container);
};

// glued acl class
$container['acl'] = function ($container) {
    return new \Glued\Classes\Acl\Acl($container);
};

// tags class
$container['tags'] = function ($container) {
    return new \Glued\Classes\Tags\Tags($container);
};

// stor class
$container['stor'] = function ($container) {
    return new \Glued\Classes\Stor\Stor($container);
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
    // NOTE: $container['auth'] closure must be before this view closure.
    // NOTE: we cant use $view->getEnvironment()->addGlobal('auth', $container->auth); 
    //       as this would do a sql query everytime we access the global
    // TODO: possibly change this into middleware later?
    $view->getEnvironment()->addGlobal('auth', [
        'check' => $container->auth->check(),
        'user' => $container->auth->user(),
    ]);

    $view->getEnvironment()->addGlobal('flash', $container->flash);

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


// glued home view
$container['HomeController'] = function ($container) {
    return new \Glued\Controllers\HomeController($container);
};


// glued authentication
$container['AuthController'] = function ($container) {
    return new \Glued\Controllers\Auth\AuthController($container);
};

// glued ACL controler, html output
$container['AclController'] = function ($container) {
    return new \Glued\Controllers\Acl\AclController($container);
};

// glued ACL controler, api
$container['AclControllerApiV1'] = function ($container) {
    return new \Glued\Controllers\Acl\AclControllerApiV1($container);
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
