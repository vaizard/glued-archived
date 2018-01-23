<?php
namespace Glued\Middleware\Permissions;
use Glued\Middleware\Middleware;

// The "you-have-to-be-in-root-group-to-see-this" middleware
class RootMiddleware extends Middleware
{

    public function __invoke($request, $response, $next)
    {
        $my_user_data = $this->container->auth->user();
        $my_groups = $this->container->permissions->user_groups($my_user_data);
        if (!in_array('root', $my_groups)) {
            
            return $this->container->view->render($response, 'forbidden.twig');
            /*
            $this->container->flash->addMessage('error', 'Sorry, you are not in root group!');
            return $response->withRedirect($this->container->router->pathFor('auth.signin'));
            */
        }
        
        $response = $next($request, $response);
        return $response;
    }

}
