<?php

namespace Glued\Classes\Auth;

class Auth

{
    // pro pouziti containeru ve funkcich teto tridy
    protected $container;
    
    public function __construct($container)
    {
        $this->container = $container;
    }
    
    
    // check if user is logged in, returns true|false
    public function check()
    {
        $user_id = $_SESSION['user_id'] ?? false;
        $authentication_id = $_SESSION['authentication_id'] ?? false;
        if ($user_id === false or $authentication_id === false) { return false; }
        else { return true; }
    }
    
    // returns data about logged user fetched from database
    public function user() {
        $user_id = $_SESSION['user_id'] ?? false;
        $authentication_id = $_SESSION['authentication_id'] ?? false;
        if ($user_id === false) return false;
        $this->container->db->where('c_uid', $user_id);
        return $this->container->db->getOne("t_users");
    }
    
    // returns just email of logged and autenticated user
    public function email() {
        $user_id = $_SESSION['user_id'] ?? false;
        $authentication_id = $_SESSION['authentication_id'] ?? false;
        if ($user_id === false or $authentication_id === false) return false;
        $this->container->db->where('c_type', 1);
        $this->container->db->where('c_uid', $authentication_id);
        $this->container->db->where('c_user_id', $user_id);
        $data = $this->container->db->getOne("t_authentication");
        return $data['c_username'];
    }
    
    // check if logged user is in root group, returns true/false
    public function root()
    {
        $user_id = $_SESSION['user_id'] ?? false;
        $authentication_id = $_SESSION['authentication_id'] ?? false;
        if ($user_id === false or $authentication_id === false) { return false; }
        else {
            $my_user_data = $this->container->auth->user();
            $my_groups = $this->container->permissions->user_groups($my_user_data);
            if (in_array('root', $my_groups)) { return true; }
            else { return false; }
        }
    }
    
    // returns screenname of any user defined by his id
    public function user_screenname($user_id) {
        $this->container->db->where('c_uid', $user_id);
        return $this->container->db->getValue("t_users", "c_screenname");
    }
    
    // nacte user id z autentikacniho id
    public function get_user_id_from_autentication($autenticate_id) {
        $this->container->db->where('c_uid', $autenticate_id);
        return $this->container->db->getValue("t_authentication", "c_user_id");
    }
    
    // signout user (delete his session)
    public function signout()
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['authentication_id']);
    }
    
    
    // attempt to sign in user, return true|false on success or failure
    public function attempt($email, $password)
    {
        $this->container->db->where('c_type', 1);
        $this->container->db->where('c_username', $email);
        $user = $this->container->db->getOne("t_authentication");
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['c_pasword'])) {
            $_SESSION['user_id'] = $user['c_user_id'];
            $_SESSION['authentication_id'] = $user['c_uid'];
            return true;
        }

        return false;
    }
}
