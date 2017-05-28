<?php

namespace Glued\Classes\Acl;

class Acl

{

    protected $container;

    private $permissions = array(
       "owner_read"   => 256,
       "owner_write"  => 128,
       "owner_delete" => 64,
       "group_read"   => 32,
       "group_write"  => 16,
       "group_delete" => 8,
       "other_read"   => 4,
       "other_write"  => 2,
       "other_delete" => 1
    );

    private $groups = array(
       "root"    => 1,
       "officer" => 2,
       "user"    => 4,
       "wheel"   => 8
    );

    public function __construct($container)
    {
        $this->container = $container;
    }


    // returns permissions array
    public function show_permissions() {
        return $this->permissions;
    }
    
    // return groups array
    public function show_groups() {
        return $this->groups;
    }
    
}
