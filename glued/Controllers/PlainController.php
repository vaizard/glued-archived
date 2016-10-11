<?php
namespace Glued\Controllers;


class PlainController 
{
    public function index($request, $response) 
    {
        var_dump($request->getParam('name'));
        return 'Plain controller';
    }
}