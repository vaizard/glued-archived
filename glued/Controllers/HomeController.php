<?php
namespace Glued\Controllers;

class HomeController extends Controller
{
    public function index($request, $response)
    {
        return $this->container->view->render($response, 'full.twig');
    }
    
    public function showTools($request, $response)
    {
        return $this->container->view->render($response, 'tools.twig');
    }
    
    
}
