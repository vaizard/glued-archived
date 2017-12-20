<?php

// Respect pulled in here because of v::with() below
use Respect\Validation\Validator as v;
//use Jsv4\Validator as jsonv;

session_start();

if (!file_exists( __DIR__ . '/settings.php')) { die("Error 500: application configuration problem."); }
require __DIR__ . '/../vendor/autoload.php';


// Instantiate the app, setup the path to our custom validation rules
$config = require __DIR__ . '/settings.php';
$app = new \Slim\App($config);
v::with('Glued\\Classes\\Validation\\Rules\\');

// Set up dependencies
require __DIR__ . '/dependencies.php';

// Register middleware
require __DIR__ . '/middleware.php';

// Register routes
require __DIR__ . '/routes.php';

/*
 * NOTE: psr-4 autoloading is turend on in composer.json. The psr-4 entry
 * "Glued\\": "glued" corresponds to the application name "Glued\" (the
 * additional backslash is for escaping) and the relative path to the "glued"
 * directory. PSR-4 will autload things according to the following key:
 * Glued=glued, Models=glued/Models, User=glued/Models/User.php, hence the
 * following will work:
 *
 *$user = new \Glued\Models\User;
 *print_r($user);
 */
