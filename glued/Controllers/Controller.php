<?php
namespace Glued\Controllers;

use Slim\Views\Twig as View; // making a name shortcut

class Controller
{

    protected $container;
    public function __construct($container) 
    // using the shortcutted View and passing 
    {
        $this->container = $container;
    }

}