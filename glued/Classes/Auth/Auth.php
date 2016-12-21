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
        $this->container->db->where('id',$user);
        return $this->container->db->getOne("users");
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
    }


    // attempt to sign in user, return true|false on success or failure
    public function attempt($email,$password)
    {
        $this->container->db->where('email',$email);
        $user = $this->container->db->getOne("users");

        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['id'];
            return true;
        }

        return false;
    }
}
