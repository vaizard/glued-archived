<?php


// we're setting up our own class so that we can use the Respect\Validation
// more easily.

namespace Glued\Classes\Validation\Rules;

use Glued\Controllers\Controller as c;
use Respect\Validation\Rules\AbstractRule;


class MatchesPassword extends AbstractRule
{
    protected $container;
    protected $user;

    public function __construct($container, $user) 
    {
        $this->container = $container;
        $this->user = $user;
    }


    public function validate($input)
    {

//print_r("x ". $this->user['password']); die();
             return password_verify($input, $this->user['password']);
             /*$this->container->db->where('email',$input);
             if ($this->container->db->getOne("users")) {
                 return false;
             } else { 
                 return true; 
             }*/
    }


}