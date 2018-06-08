<?php
namespace Glued\Middleware\Auth;
use Glued\Middleware\Middleware;


// The "you-have-to-be-NOT-authenticated-to-see-this" middleware
class GuestMiddleware extends Middleware
{

    public function __invoke($request, $response, $next)
    {
        if ($this->container->auth_user->check) {

            $this->container->flash->addMessage('info', 'You are already signed in.');
            return $response->withRedirect($this->container->router->pathFor('home'));
        }

        $response = $next($request, $response);
        return $response;
    }

}
