<?php
namespace Glued\Controllers;

use Slim\Views\Twig as View; // making a name shortcut

class HomeController extends Controller
{
    public function index($request, $response) 
    {
        return $this->container->view->render($response, 'full.twig');
    }
}