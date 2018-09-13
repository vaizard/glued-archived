<?php

namespace Glued\Classes\Auth_user;

class Auth_user

{
    public $check;
    public $user;
    public $email;
    public $root;
    public $user_id;
    public $authentication_id;
    
    public function __construct($container)
    {
        $this->check = $container->auth->check();
        $this->user = $container->auth->user();
        $this->email = $container->auth->email();
        $this->root = $container->auth->root();
        $this->user_id = $_SESSION['user_id'] ?? false;
        $this->authentication_id = $_SESSION['authentication_id'] ?? false;
    }
}
