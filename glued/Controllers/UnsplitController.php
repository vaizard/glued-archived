<?php
namespace Glued\Controllers;

use Slim\Views\Twig as View; // making a name shortcut

class UnsplitController
{

    protected $view;
    public function __construct(View $view) 
    // using the shortcutted View and passing 
    {
        $this->view = $view;
    }

    public function index($request, $response) 
    {
        return $this->view->render($response, 'full.twig');
    }
}