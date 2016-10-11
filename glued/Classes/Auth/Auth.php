<?php

namespace Glued\Classes\Auth;

class Auth

{

    protected $container;
    public function __construct($container) 
    {
         $this->container = $container;
    }

    public function user() {
         $user = $_SESSION['user'] ?? false;
         if ($user === false) return false;
         $this->container->db->where('id',$user);
         return $this->container->db->getOne("users");
    }

    // check if logged in
    public function check()
    {
        return $_SESSION['user'] ?? false;
    }

    public function signout()
    {
        unset($_SESSION['user']);
    }


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