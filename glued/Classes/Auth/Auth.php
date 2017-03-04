<?php

namespace Glued\Classes\Auth;

class Auth

{

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }


    // returns data about user fetched from database
    public function user() {
        $user = $_SESSION['user'] ?? false;
        if ($user === false) return false;
        $this->container->db->where('c_uid', $user);
        return $this->container->db->getOne("t_users");
    }
    

    // check if user is logged in, returns true|false
    public function check()
    {
        return $_SESSION['user'] ?? false;
    }


    // signout user (delete his session)
    public function signout()
    {
        unset($_SESSION['user']);
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
            $_SESSION['user'] = $user['c_user_id'];
            $_SESSION['authentication_id'] = $user['c_uid'];
            return true;
        }

        return false;
    }
}
