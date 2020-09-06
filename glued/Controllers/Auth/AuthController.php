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
            $this->container->flash->addMessage('error', 'Could not sign in with those details.');
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
            'email' => v::noWhitespace()->notEmpty()->email()->emailAvailable($this->container, false),
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
        
        // defaultne je ve skupine 2, users
        $default_group = 2;
        // pokud je to ovsem prvni zakladany user, musi dostat i root prava, tedy musi byt ve skupinach root a user,  1 + 2 = 3
        $aspon_jeden = $this->container->db->get('t_users', 1);
        if ($this->container->db->count == 0) { $default_group = 3; }
        
        
        // insert $data into db with transaction, 2 tables
        $this->container->db->startTransaction();
        
        // do t_users vlozime zakladni zaznam, a funkce nam vraci zalozene id
        $data1 = Array (
            "c_screenname"     => $request->getParam('name'),
            "c_group_mememberships" => $default_group
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
            $new_autentication_id = $this->container->db->insert('t_authentication', $data2);
            
            if ($new_autentication_id) {
                $this->container->logger->info("Auth: user ".$request->getParam('email')." created");
                $this->container->flash->addMessage('info', 'You have been signed up');
                $this->container->db->commit();
                // user successfully signed up, so we'll sign him in directly, then
                $this->container->auth->attempt($request->getParam('email'), $request->getParam('password'));
                // redirect home
                return $response->withRedirect($this->container->router->pathFor('home'));
            }
            else {
                $this->container->logger->warn("Auth: user creation ".$request->getParam('email')." failed");
            }
        }
        else {
            $this->container->logger->warn("Auth: user creation ".$request->getParam('email')." failed");
        }
        
        // creation error, redirect to form again
        $this->container->db->rollback();
        $this->container->flash->addMessage('error', 'User creation failed.');
        return $response->withRedirect($this->container->router->pathFor('auth.signup'));
    }

    // responds to the settings (with forms for change password, screenname and email)
    public function getSettings($request, $response)
    {
        // zjistime si jazyky
        $vystup = '
        <div>server accept language: '.$_SERVER['HTTP_ACCEPT_LANGUAGE'].'</div>
        <div>locale_accept_from_http: '.locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']).'</div>
        ';
        
        return $this->container->view->render($response, 'auth/settings.twig', array('pomocny_vystup' => $vystup));
    }


    // responds to the change password post request (tries to change user's
    // password, redirects him to different locations based on success|failure.
    public function PostChangePassword($request, $response)
    {
        $user_id = $_SESSION['user_id'] ?? false;
        $authentication_id = $_SESSION['authentication_id'] ?? false;
        
        if ($user_id and $authentication_id) {
            
            // matchesPassword() is a custom validation rule, see Classes/Validation
            // using $this->container->auth->user() as its parameter is a
            // preparation for cases when user's password can be reset by an admin
            // as well (not only the user himselft)
            
            // zatim udelame jen nejjednodussi pripad, ze menime heslo prihlaseneho uzivatele. pozdeji zde bude nejake vetveni
            $change_user_id = $user_id;
            $change_authentication_id = $authentication_id;
            
            $validation = $this->container->validator->validate($request, [
                'password_old' => v::noWhitespace()->notEmpty()->matchesPassword($this->container, $change_user_id, $change_authentication_id),
                'password' => v::noWhitespace()->notEmpty(),
            ]);
            
            // on validation failure redirect back to the form. the rest of this
            // function won't get exectuted
            if ($validation->failed()) {
                $this->container->logger->warn("Password change failed. Validation error.");
                return $response->withRedirect($this->container->router->pathFor('auth.settings'));
            }
            
            // change the password, emit flash message and redirect
            $password = $request->getParam('password');
            $this->container->db->where('c_type', 1);
            $this->container->db->where('c_uid', $change_authentication_id);
            $this->container->db->where('c_user_id', $change_user_id);
            $update = $this->container->db->update('t_authentication', Array ( 'c_pasword' => password_hash($password, PASSWORD_DEFAULT)  ));
            
            if (!$update) {
                $this->container->logger->warn("Password change failed. DB error.");
                return $response->withRedirect($this->container->router->pathFor('auth.settings'));
            }
            else {
                $this->container->flash->addMessage('info', 'Your password was changed');
                return $response->withRedirect($this->container->router->pathFor('home'));
            }
        }
        
    }

    // zmeni screenname a login email ze settings stranky
    public function postChangeIdentification($request, $response)
    {
        $user_id = $_SESSION['user_id'] ?? false;   // urcuje id v tabulce se screenname a user id v tabulce s login mailem
        $authentication_id = $_SESSION['authentication_id'] ?? false;   // urcuje id radku v tabulce s login emailem
        
        if ($user_id and $authentication_id) {
            
            // emailAvailable overi ze takovy email nema nekdo jiny
            
            $change_user_id = $user_id;
            $change_authentication_id = $authentication_id;
            
            $validation = $this->container->validator->validate($request, [
                'email' => v::noWhitespace()->notEmpty()->email()->emailAvailable($this->container, $change_authentication_id),
                'name'  => v::noWhitespace()->notEmpty()->alpha()
            ]);
            
            // on validation failure redirect back to the form. the rest of this
            // function won't get exectuted
            if ($validation->failed()) {
                $this->container->logger->warn("Identification change failed. Validation error.");
                return $response->withRedirect($this->container->router->pathFor('auth.settings'));
            }
            
            // TODO, menime dve veci ve dvou ruznych tabulkach, meli bychom udelat transaction a commit, pripadne rollback
            
            // change the email, emit flash message and redirect
            $email = $request->getParam('email');
            $this->container->db->where('c_type', 1);
            $this->container->db->where('c_uid', $change_authentication_id);
            $this->container->db->where('c_user_id', $change_user_id);
            $update = $this->container->db->update('t_authentication', Array ( 'c_username' => $email ));
            
            $name = $request->getParam('name');
            $language = $request->getParam('language');
            $this->container->db->where('c_uid', $change_user_id);
            $update2 = $this->container->db->update('t_users', Array ( 'c_screenname' => $name, 'c_language' => $language ));
            
            if (!$update) {
                $this->container->logger->warn("Email and Name change failed. DB error.");
                return $response->withRedirect($this->container->router->pathFor('auth.settings'));
            }
            else {
                $this->container->flash->addMessage('info', 'Your email and name were changed');
                return $response->withRedirect($this->container->router->pathFor('home'));
            }
        }
        
    }
    
    // vypis profilu
    public function getProfile($request, $response)
    {
        $user_id = $this->container->auth_user->user_id;
        $this->container->db->where("c_uid", $user_id);
        $data = $this->container->db->getOne('t_users');
        
        /**
         * JSON data to html table
         * 
         * @param object $data
         * 
         */
        function jsonToTable ($data)
        {
            $table = '
            <table class="json-table" width="100%">
            ';
            foreach ($data as $key => $value) {
                $table .= '
                <tr valign="top">
                ';
                if ( ! is_numeric($key)) {
                    $table .= '
                    <td>
                        <strong>'. $key .':</strong>
                    </td>
                    <td>
                    ';
                } else {
                    $table .= '
                    <td colspan="2">
                    ';
                }
                if (is_object($value) || is_array($value)) {
                    $table .= jsonToTable($value);
                } else {
                    $table .= $value;
                }
                $table .= '
                    </td>
                </tr>
                ';
            }
            $table .= '
            </table>
            ';
            return $table;
        }
        
        if (empty($data['profile_data'])) { $profile_output = '<p>profil zatím nebyl editován</p>'; }
        else { $profile_output = jsonToTable(json_decode($data['profile_data'])->data); }
        
        return $this->container->view->render($response, 'auth/profile.twig', array('profile_output' => $profile_output));
    }
    
    // editace profilu, json form
    public function editProfile($request, $response)
    {
        $user_id = $this->container->auth_user->user_id;
        $this->container->db->where("c_uid", $user_id);
        $data = $this->container->db->getOne('t_users');
        
        // zde je mozne zadat nejaky vystup okolo formu
        $form_output = '';
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/profile_form_ui.json');
        
        // schema celeho editacniho formulare.je prebrane z adresare contact
        $json_schema_output = file_get_contents(__DIR__.'/../Contacts/V1/jsonschemas/contact.json');
        
        // zakladni data pro editaci
        if (!empty($data['profile_data'])) { $json_formdata_output = $data['profile_data']; }
        else { $json_formdata_output = '{"data":{}}'; }
        
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('auth.profile.api.edit').$args['id'].'",
      dataType: "text",
      type: "PUT",
      data: "billdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        
        ReactDOM.render((<div><h1>Record was updated succesfully</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
        
      },
      error: function(xhr, status, err) {
        alert(status + err + data);
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        return $this->container->view->render($response, 'auth/editprofile.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output,
            'json_formdata_render_custom_array' => '1'
        ));
    }
    
    

}
