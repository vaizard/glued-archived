<?php

namespace Glued\Classes\Stor;

class Stor

{
    protected $container;
    
    // konstruktor
    public function __construct($container)
    {
        $this->container = $container;
    }
    
    // cti tagy ke dvojici tabulka uid
    public function read_tags($table, $uid) {
        $pole_tagu = array();
        
        $this->container->db->where("c_table", $table);
        $this->container->db->where("c_uid", $uid);
        $bills = $this->container->db->get('t_tag_assignments');
        if (count($bills) > 0) {
            foreach ($bills as $data) {
                $pole_tagu[] = array('name' => $data['c_tagname'], 'value' => $data['c_tagvalue']);
            }
        }
        
        return $pole_tagu;
    }
    
    // cti hodnotu tagu
    public function read_tag_value($table, $uid, $tagname) {
        $this->container->db->where("c_table", $table);
        $this->container->db->where("c_uid", $uid);
        $this->container->db->where("c_tagname", $tagname);
        $data = $this->container->db->getOne('t_tag_assignments');
        
        if (count($data) == 0) {
            return false;
        }
        else {
            return $data['c_tagvalue'];
        }
    }
    
    // vloz tag
    public function insert_tag($table, $uid, $tagname, $tagvalue, $system = 0) {
        if ($this->container->tags->read_tag_value($table, $uid, $tagname) !== false) {
            $data = Array ("c_table" => $table, "c_uid" => $uid, "c_tagname" => $tagname, "c_tagvalue" => $tagvalue, "c_system" => $system);
            $insert = $this->container->db->insert('t_tag_assignments', $data);
            if ($insert) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
}
