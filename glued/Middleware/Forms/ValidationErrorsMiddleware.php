<?php
namespace Glued\Middleware\Forms;
use Glued\Middleware\Middleware;


class ValidationErrorsMiddleware extends Middleware
{

    public function __invoke($request, $response, $next)
    {

        // exposing the errors from $_SESSION['forms_validationerrors'] as a global
        // unsetting the last errors in session
        $this->container->view->getEnvironment()->addGlobal('validationerrors',$_SESSION['validationerrors'] ?? null);
        unset($_SESSION['validationerrors']);

        $response = $next($request, $response);
        return $response;
    }

}
