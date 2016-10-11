<?php
namespace Glued\Middleware;

class ValidationErrorsMiddleware extends Middleware
{

    public function __invoke($request, $response, $next) 
    {

        // getting the errors from session
        $ve = $_SESSION['validationerrors'] ?? false; // if array item is not set, assume $ve to be null
        $this->container->view->getEnvironment()->addGlobal('validationerrors',$_SESSION['validationerrors'] ?? null);
        unset($_SESSION['validationerrors']);
        $response = $next($request, $response);
        return $response;
    }

}