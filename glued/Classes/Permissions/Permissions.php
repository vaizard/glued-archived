<?php

namespace Glued\Classes\Permissions;

class Permissions

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
       "user"    => 2,
       "bot"   => 4
    );

    private $tables = array(
       "platby_mzdy",
       "timepixels",
       "t_assets_items",
       "t_consumables_items",
       "t_parts_items"
    );

    private $statuses = array(
       "open"    => 1,
       "published" => 2,
       "available" => 4
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
    
    // return tables array
    public function show_tables() {
        return $this->tables;
    }
    
    // return statuses array
    public function show_statuses() {
        return $this->statuses;
    }
    
    // vrati pole skupin, kde je uzivatel
    public function user_groups($user) {
        $groups = $this->groups;
        
        $membership = array();
        foreach ($groups as $gname => $gid) {
            if ($user['c_group_mememberships'] & $gid) { $membership[] = $gname; }
        }
        
        return $membership;
    }
    
    // read table privileges, TODO zabudovat negaci, primarne pujde o pravo na insert
    public function read_table_privileges($tbl, $virtual_user = false) {
        
        $pole_akci = array();
        $groups = $this->groups;
        
        // nacteme data nalogovaneho usera. pokud virtual = true, musime nacist data fiktivniho usera (v session v jine promenne), na coz neni udelana funkce v auth, TODO probrat bezpecnost
        $user_data = $this->container->auth->user();
        
        $user_id     = $user_data['c_uid'];
        $user_groups = $user_data['c_group_mememberships'];
        
        $query = "
        select ac.c_title
        from
            t_action as ac
            -- Privileges that apply to the table and grant the given action
            -- Not an inner join because the action may be granted even if there is no
            -- privilege granting it.  For example, root users can take all actions.
            left outer join t_privileges as pr
                on pr.c_related_table = '$tbl'
                    and pr.c_action = ac.c_title
                    and pr.c_type = 'table'
        where
            -- The action must apply to tables (NOT apply to objects)
            (ac.c_apply_object = 0) and (
                -- Members of the 'root' group are always allowed to do everything
                ($user_groups & $groups[root] <> 0)
                -- user privileges
                or (pr.c_role = 'user' and pr.c_who = $user_id)
                -- group privileges
                or (pr.c_role = 'group' and (pr.c_who & $user_groups <> 0)))
        ";
        
        $result = $this->container->mysqli->query($query);
        
        
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pole_akci[] = $row["c_title"];
            }
        }
        
        //$pole_akci[] = $groups;
        //$pole_akci[] = $user_groups;
        //$pole_akci[] = $user_id;
        
        
        return $pole_akci;
    }
    
    // read global privileges of table, TODO zabudovat negaci, primarne pujde o pravo cist cizi zaznamy
    public function read_global_privileges($tbl, $virtual_user = false) {
        
        $pole_akci = array();
        $groups = $this->groups;
        
        // nacteme data nalogovaneho usera. pokud virtual = true, musime nacist data fiktivniho usera (v session v jine promenne), na coz neni udelana funkce v auth, TODO probrat bezpecnost
        $user_data = $this->container->auth->user();
        
        $user_id     = $user_data['c_uid'];
        $user_groups = $user_data['c_group_mememberships'];
        
        $query = "
        select ac.c_title
        from
            t_action as ac
            -- Privileges that apply to the table and grant the given action
            -- Not an inner join because the action may be granted even if there is no
            -- privilege granting it.  For example, root users can take all actions.
            left outer join t_privileges as pr
                on pr.c_related_table = '$tbl'
                    and pr.c_action = ac.c_title
                    and pr.c_type = 'global'
        where
            -- The action must apply to objects
            (ac.c_apply_object = 1) and (
                -- Members of the 'root' group are always allowed to do everything
                ($user_groups & $groups[root] <> 0)
                -- user privileges
                or (pr.c_role = 'user' and pr.c_who = $user_id)
                -- group privileges
                or (pr.c_role = 'group' and (pr.c_who & $user_groups <> 0)))
        ";
        
        $result = $this->container->mysqli->query($query);
        
        
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pole_akci[] = $row["c_title"];
            }
        }
        
        return $pole_akci;
    }
    
    // read creator global privileges of table. overujeme v podstate obecne, jestli v dane tabulce ma creator pravo pracovat se svyma vytvorenyma vecma
    public function read_creator_privileges($tbl, $virtual_user = false) {
        
        $pole_akci = array();
        $groups = $this->groups;
        
        // nacteme data nalogovaneho usera. pokud virtual = true, musime nacist data fiktivniho usera (v session v jine promenne), na coz neni udelana funkce v auth, TODO probrat bezpecnost
        $user_data = $this->container->auth->user();
        
        $user_id     = $user_data['c_uid'];
        $user_groups = $user_data['c_group_mememberships'];
        
        $query = "
        select ac.c_title
        from
            t_action as ac
            -- Privileges that apply to the table and grant the given action
            -- Not an inner join because the action may be granted even if there is no
            -- privilege granting it.  For example, root users can take all actions.
            left outer join t_privileges as pr
                on pr.c_related_table = '$tbl'
                    and pr.c_action = ac.c_title
                    and pr.c_type = 'global'
        where
            -- The action must apply to objects
            (ac.c_apply_object = 1) and (
                -- Members of the 'root' group are always allowed to do everything
                ($user_groups & $groups[root] <> 0)
                -- creator privileges
                or (pr.c_role = 'creator' and pr.c_neg = '0') )
        ";
        
        $result = $this->container->mysqli->query($query);
        
        
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pole_akci[] = $row["c_title"];
            }
        }
        
        return $pole_akci;
    }
    
    
    // overeni jestli mam pravo delat table akci
    public function have_table_action($tbl, $action_name, $virtual_user = false) {
        $allowed_actions = $this->read_table_privileges($tbl, $virtual_user);
        
        if (in_array($action_name, $allowed_actions)) { return true; }
        else { return false; }
    }
    
    // overeni jestli mam pravo delat global akci
    public function have_global_action($tbl, $action_name, $virtual_user = false) {
        $allowed_actions = $this->read_global_privileges($tbl, $virtual_user);
        
        if (in_array($action_name, $allowed_actions)) { return true; }
        else { return false; }
    }
    
    // overeni jestli mam pravo delat global akci v roli creator, overuju ale i globalni akci v roli group a user, protoze to muze creatora prebit a timpadem bych tu akci mit mel
    // TODO promyslet jak s negacema, napriklad co kdyz to budu mit globalne zakazane a creatorove povolene? co je silnejsi?
    public function have_creator_action($tbl, $action_name, $virtual_user = false) {
        $allowed_actions = $this->read_creator_privileges($tbl, $virtual_user);
        $allowed_global_actions = $this->read_global_privileges($tbl, $virtual_user);
        
        if (in_array($action_name, $allowed_actions) or in_array($action_name, $allowed_global_actions)) { return true; }
        else { return false; }
    }
    
    // fukce ktera naplni modal pro danou tabulku (kdo ma na co pravo)
    public function modal_output_rights($tbl, $type) {
        $vystup_privileg = '';
        
        if ($type == 'table') {
            $this->container->db->where("c_type", 'table');
            $this->container->db->where("c_related_table", $tbl);
            $privileges = $this->container->db->get('t_privileges');
            if ($this->container->db->count > 0) {
                foreach ($privileges as $privilege) {
                    // pokud je role user, nacteme jeho id a jmeno
                    if ($privilege['c_role'] == 'user') {
                        $kdo = 'User <strong>'.$this->container->auth->user_screenname($privilege['c_who']).'</strong>';
                    }
                    else if ($privilege['c_role'] == 'group') {
                        $kdo = 'Group <strong>'.$privilege['c_who'].'</strong>';
                    }
                    else if ($privilege['c_role'] == 'creator') {
                        $kdo = '<strong>Creator</strong>';
                    }
                    $vystup_privileg .= '<div>'.$kdo.' '.($privilege['c_neg'] == 0?'can':'can not !').' <strong>'.$privilege['c_action'].'</strong></div>';
                }
            }
            else {
                $vystup_privileg .= '<div>no privileges at the moment</div>';
            }
        }
        else if ($type == 'global') {
            $this->container->db->where("c_type", 'global');
            $this->container->db->where("c_related_table", $tbl);
            $privileges = $this->container->db->get('t_privileges');
            if ($this->container->db->count > 0) {
                foreach ($privileges as $privilege) {
                    // pokud je role user, nacteme jeho id a jmeno
                    if ($privilege['c_role'] == 'user') {
                        $kdo = 'User <strong>'.$this->container->auth->user_screenname($privilege['c_who']).'</strong>';
                    }
                    else if ($privilege['c_role'] == 'group') {
                        $kdo = 'Group <strong>'.$privilege['c_who'].'</strong>';
                    }
                    else if ($privilege['c_role'] == 'creator') {
                        $kdo = '<strong>Creator</strong>';
                    }
                    $vystup_privileg .= '<div>'.$kdo.' '.($privilege['c_neg'] == 0?'can':'can not !').' <strong>'.$privilege['c_action'].'</strong></div>';
                }
            }
            else {
                $vystup_privileg .= '<div>no privileges at the moment</div>';
            }
        }
        
        return $vystup_privileg;
    }
    
    
}
