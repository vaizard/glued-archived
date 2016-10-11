<?php
namespace Glued\Controllers;

// making a name shortcut for extending controllers
use Slim\Views\Twig as View;

class Controller
{

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

}
