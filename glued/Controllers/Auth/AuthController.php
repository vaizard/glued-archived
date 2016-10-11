<?php
namespace Glued\Controllers\Auth;

use Glued\Controllers\Controller; // needed because Auth is in a directory below
use Glued\Models\Mapper;
use Respect\Validation\Validator as v;


class AuthController extends Controller
{



    public function getSignOut($request, $response)
    {
        $this->container->auth->signout();
        return $response->withRedirect($this->container->router->pathFor('home'));

    }




    public function getSignIn($request, $response)
    {
        return $this->container->view->render($response, 'auth/signin.twig');
    }

    public function postSignIn($request, $response) 
    {
        // emailAvailable() needs $this->container as a parameter to get to use DI database connection
        $validation = $this->container->validator->validate($request, [
             'email' => v::noWhitespace()->notEmpty()->email(),
             'password' => v::noWhitespace()->notEmpty(),
        ]);

        if ($validation->failed()) {
           // on validation failure redirect back, 
           // the rest of the function won't happen
          return $response->withRedirect($this->container->router->pathFor('auth.signin'));
        }

        $auth = $this->container->auth->attempt(
            $request->getParam('email'),
            $request->getParam('password')
        );

        if(!$auth) {
             $this->container->flash->addMessage('error', 'Could not sign in with those details.');
             return $response->withRedirect($this->container->router->pathFor('auth.signin'));
        }

         return $response->withRedirect($this->container->router->pathFor('home'));

        //var_dump($request->getParams());
        /*
        $data = Array ("email"     => $request->getParam('email'),
                       "name"      => $request->getParam('name'),
                       "password"  => password_hash($request->getParam('password'), PASSWORD_DEFAULT),
                      );
        //print_r($data);
        $user = $this->container->db->insert ('users', $data);
         */

/*
        if ($user)
              $this->container->logger->info("Auth: user ".$data['email']." created");
        else
              $this->container->logger->warn("Auth: user creation ".$data['email']." failed");
        return $response->withRedirect($this->container->router->pathFor('home'));
*/
    }



    public function getSignUp($request, $response)
    {
        return $this->container->view->render($response, 'auth/signup.twig');
    }

    public function postSignUp($request, $response) 
    {
        // emailAvailable() needs $this->container as a parameter to get to use DI database connection
        $validation = $this->container->validator->validate($request, [
             'email' => v::noWhitespace()->notEmpty()->email()->emailAvailable($this->container),
             'name'  => v::noWhitespace()->notEmpty()->alpha(),
             'password' => v::noWhitespace()->notEmpty(),
        ]);

        if ($validation->failed()) {
           // on validation failure redirect back, 
           // the rest of the function won't happen
          return $response->withRedirect($this->container->router->pathFor('auth.signup'));
        }

        //var_dump($request->getParams());
        $data = Array ("email"     => $request->getParam('email'),
                       "name"      => $request->getParam('name'),
                       "password"  => password_hash($request->getParam('password'), PASSWORD_DEFAULT),
                      );
        //print_r($data);
        $user = $this->container->db->insert ('users', $data);
        if ($user) {
              $this->container->logger->info("Auth: user ".$data['email']." created");
              $this->container->flash->addMessage('info', 'You have been signed up');
        } else
              $this->container->logger->warn("Auth: user creation ".$data['email']." failed");

        $this->container->auth->attempt($data['email'], $request->getParam('password')); // signin on signup
        return $response->withRedirect($this->container->router->pathFor('home'));

    }


    public function getChangePassword($request, $response)
    {
        return $this->container->view->render($response, 'auth/changepassword.twig');
    }

    public function PostChangePassword($request, $response)
    {

        // passing $this->container->auth->user() to matchesPassword so that not only
        // a user himself but also an admin from the admin panel, can change the user's password
        // so we're not tying the validation down to the currently authenticated user.
        $validation = $this->container->validator->validate($request, [
             'password_old' => v::noWhitespace()->notEmpty()->matchesPassword($this->container, $this->container->auth->user()),
             'password' => v::noWhitespace()->notEmpty(),
        ]);

        if ($validation->failed()) {
           // on validation failure redirect back, 
           // the rest of the function won't happen
          return $response->withRedirect($this->container->router->pathFor('auth.password.change'));
        }

        $user_id = $_SESSION['user'] ?? false;
        if ($user_id) {
          $password = $request->getParam('password');
          $this->container->db->where('id', $user_id);
          $this->container->db->update('users', Array ( 'password' => password_hash($password, PASSWORD_DEFAULT)  ));
          $this->container->flash->addMessage('info', 'Your password was changed');
          return $response->withRedirect($this->container->router->pathFor('home'));
       }

    }



}