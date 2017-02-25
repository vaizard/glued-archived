<?php
namespace Glued\Controllers\Auth;

use Glued\Controllers\Controller; // needed because Auth is in a directory below
use Respect\Validation\Validator as v;

class AuthController extends Controller
{

    public function getSignOut($request, $response)
    // responds to the signout get request (signs user out and redirects him)
    {
        $this->container->auth->signout();
        return $response->withRedirect($this->container->router->pathFor('home'));

    }


    // responds to the signin get request (shows signin form)
    public function getSignIn($request, $response)
    {
        return $this->container->view->render($response, 'auth/signin.twig');
    }


    // responds to the signin post request (tries to sign user in, redirects him
    // to different locations based on success|failure.
    public function postSignIn($request, $response)
    {
        // validate user input
        $validation = $this->container->validator->validate($request, [
            'email' => v::noWhitespace()->notEmpty()->email(),
            'password' => v::noWhitespace()->notEmpty(),
        ]);

        // on validation failure redirect back to signin form. the rest of this
        // function won't get exectuted
        if ($validation->failed()) {
            return $response->withRedirect($this->container->router->pathFor('auth.signin'));
        }

        // attempt to signin
        $auth = $this->container->auth->attempt(
            $request->getParam('email'),
            $request->getParam('password')
        );

        if(!$auth) {
            $this->container->flash->addMessage('error', 'Could not sign in with those details.');
            return $response->withRedirect($this->container->router->pathFor('auth.signin'));
        }

        return $response->withRedirect($this->container->router->pathFor('home'));
    }


    // responds to the signup get request (shows signup form)
    public function getSignUp($request, $response)
    {
        return $this->container->view->render($response, 'auth/signup.twig');
    }


    // responds to the signup post request (tries to sign user up, redirects him
    // to different locations based on success|failure.
    public function postSignUp($request, $response)
    {
        // Don't forget that emailAvailable() is our custom rule located in
        // Classes/Validation/Rules. Needs $this->container passed as a param
        // to get to DIC database connection
        $validation = $this->container->validator->validate($request, [
            'email' => v::noWhitespace()->notEmpty()->email()->emailAvailable($this->container),
            'name'  => v::noWhitespace()->notEmpty()->alpha(),
            'password' => v::noWhitespace()->notEmpty(),
        ]);
        
        // on validation failure redirect back to signup form. the rest of this
        // function won't get exectuted
        if ($validation->failed()) {
            return $response->withRedirect($this->container->router->pathFor('auth.signup'));
        }
        
        // DEBUG
        // var_dump($request->getParams());
        
        // insert $data into db with transaction, 2 tables
        $this->container->db->startTransaction();
        
        // do t_users vlozime zakladni zaznam, a funkce nam vraci zalozene id
        $data1 = Array (
            "c_screenname"     => $request->getParam('name')
        );
        $new_user_id = $this->container->db->insert ('t_users', $data1);
        
        // emit flash message and log result
        if ($new_user_id) {
            // dale to vlozime do t_authentication
            $data2 = Array (
                "c_user_id"      => $new_user_id,
                "c_type" => 1,
                "c_username"     => $request->getParam('email'),
                "c_pasword"  => password_hash($request->getParam('password'), PASSWORD_DEFAULT),
            );
            $new_autentication_id = $this->container->db->insert ('t_authentication', $data2);
            
            if ($new_autentication_id) {
                $this->container->logger->info("Auth: user ".$request->getParam('email')." created");
                $this->container->flash->addMessage('info', 'You have been signed up');
                $this->container->db->commit();
            }
            else {
                $this->container->logger->warn("Auth: user creation ".$request->getParam('email')." failed");
                $this->container->db->rollback();
            }
        }
        else {
            $this->container->logger->warn("Auth: user creation ".$request->getParam('email')." failed");
            $this->container->db->rollback();
        }
        
        // user successfully signed up, so we'll sign him in directly, then
        // redirect home
        $this->container->auth->attempt($request->getParam('email'), $request->getParam('password'));
        return $response->withRedirect($this->container->router->pathFor('home'));
    }

    // responds to the change password get request (shows signin form)
    public function getChangePassword($request, $response)
    {
        return $this->container->view->render($response, 'auth/changepassword.twig');
    }


    // responds to the change password post request (tries to change user's
    // password, redirects him to different locations based on success|failure.
    public function PostChangePassword($request, $response)
    {
        // matchesPassword() is a custom validation rule, see Classes/Validation
        // using $this->container->auth->user() as its parameter is a
        // preparation for cases when user's password can be reset by an admin
        // as well (not only the user himselft)
        $validation = $this->container->validator->validate($request, [
            'password_old' => v::noWhitespace()->notEmpty()->matchesPassword($this->container, $this->container->auth->user()),
            'password' => v::noWhitespace()->notEmpty(),
        ]);

        // on validation failure redirect back to the form. the rest of this
        // function won't get exectuted
        if ($validation->failed()) {
            return $response->withRedirect($this->container->router->pathFor('auth.password.change'));
        }

        // change the password, emit flash message and redirect
        // TODO error handling on failed db->update
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
