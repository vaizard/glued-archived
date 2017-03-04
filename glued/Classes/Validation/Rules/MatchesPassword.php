<?php


// we're setting up our own class so that we can use the Respect\Validation
// more easily.

namespace Glued\Classes\Validation\Rules;

use Glued\Controllers\Controller as c;
use Respect\Validation\Rules\AbstractRule;


class MatchesPassword extends AbstractRule
{
    protected $container;
    protected $user_id;
    protected $authentication_id;
    
    public function __construct($container, $user_id, $authentication_id) 
    {
        $this->container = $container;
        $this->user_id = $user_id;
        $this->authentication_id = $authentication_id;
    }

    // ze v input bude to heslo zajistuje konstrukce validatoru
    public function validate($input)
    {
        $this->container->db->where('c_type', 1);
        $this->container->db->where('c_user_id', $this->user_id);
        $this->container->db->where('c_uid', $this->authentication_id);
        $user_data = $this->container->db->getOne("t_authentication");
        if (!$user_data) {
            return false; // pokud tam takovy zaznam neni, je to jasne false
        }
        
        return password_verify($input, $user_data['c_pasword']);
    }


}