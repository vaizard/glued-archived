<?php


// we're setting up our own class so that we can use the Respect\Validation
// more easily.

namespace Glued\Classes\Validation\Rules;

use Glued\Controllers\Controller as c;
use Respect\Validation\Rules\AbstractRule;


class EmailAvailable extends AbstractRule
{
    protected $container;
    public function __construct($container) 
    {
        $this->container = $container;
    }


    public function validate($input)
    {
        $this->container->db->where('c_type', 1);
        $this->container->db->where('c_username', $input);
        if ($this->container->db->getOne("t_authentication")) {
            return false;
        } else { 
            return true; 
        }
    }


}