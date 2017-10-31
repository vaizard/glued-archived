<?php


// we're setting up our own class so that we can use the Respect\Validation
// more easily.

namespace Glued\Classes\Validation\Rules;

use Glued\Controllers\Controller as c;
use Respect\Validation\Rules\AbstractRule;


class EmailAvailable extends AbstractRule
{
    protected $container;
    public function __construct($container, $authentication_id) 
    {
        $this->container = $container;
        $this->authentication_id = $authentication_id;
    }

    // ze input bude ten email zajistuje konstrukce validatoru, protoze je to prirazeno jako pravidlo pro email
    public function validate($input)
    {
        $this->container->db->where('c_type', 1);
        $this->container->db->where('c_username', $input);
        // pokud neni autentication false, pridame to jako podminku ze to musi byt jiny zaznam
        if ($this->authentication_id !== false) {
            $this->container->db->where('c_uid', $this->authentication_id, '!=');
        }
        if ($this->container->db->getOne("t_authentication")) {
            return false;   // pokud tam je, vracime false, jakoze neni available
        } else { 
            return true; // pokud tam neni, vracime true, jakoze je available
        }
    }


}