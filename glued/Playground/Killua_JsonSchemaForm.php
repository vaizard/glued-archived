<?php

namespace Glued\Playground;
use Glued\Controllers\Controller;
#use Jsv4\Validator as jsonv;
#use Jsv4\SchemaStore;

class Killua_JsonSchemaForm extends Controller
{
    public function json_moz($request, $response)
    {
        return $this->container->view->render($response, 'jsonschema.mozilla.twig');
    }

}


