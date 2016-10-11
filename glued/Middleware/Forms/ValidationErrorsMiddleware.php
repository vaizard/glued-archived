<?php
namespace Glued\Middleware;

class ValidationErrorsMiddleware extends Middleware
{

    public function __invoke($request, $response, $next)
    {

        // exposing the errors from $_SESSION['validationerrors'] as a global
        // unsetting the last errors in session
        $this->container->view->getEnvironment()->addGlobal('validationerrors',$_SESSION['validationerrors'] ?? null);
        unset($_SESSION['validationerrors']);

        $response = $next($request, $response);
        return $response;
    }

}
