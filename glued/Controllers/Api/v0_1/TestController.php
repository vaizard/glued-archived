<?php

namespace Glued\Controllers\Api\v0_1;
use Glued\Controllers\Controller;

class TestController extends Controller
{
    public function get($request, $response, $args)
    {
       $response->getBody()->write('{ "lala": "'.$args['id'].'" }');
       return $response->withStatus(201)->withHeader('Content-Type', 'aplication/json');
    }

}
