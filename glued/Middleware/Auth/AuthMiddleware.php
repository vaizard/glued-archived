<?php
namespace Glued\Middleware\Auth;
use Glued\Middleware\Middleware;

// The "you-have-to-be-authenticated-to-see-this" middleware
class AuthMiddleware extends Middleware
{

    public function __invoke($request, $response, $next)
    {
        //if (!$this->container->auth->check()) {
        if (!$this->container->auth_user->check) {
            $this->container->flash->addMessage('error', 'Please sign in first, thanks!');
            return $response->withRedirect($this->container->router->pathFor('auth.signin'));
        }

        $response = $next($request, $response);
        return $response;
    }

}
