<?php

namespace Glued\Classes\Stor;

class Stor

{
    protected $container;
    
    public $app_dirs = array(
       "my_files"    => 'My private files',
       "my_owned"    => 'My owned files',
       "users"    => 'Users private files',
       "assets"    => 'Assets',
       "consumables"    => 'Consumables',
       "parts"    => 'Parts'
    );
    
    // prevod path na tabulku, kvuli predzjisteni prav
    public $app_tables = array(
       "users"    => 't_authentication',
       "assets"    => 't_assets_items',
       "consumables"    => 't_consumables_items',
       "parts"    => 't_parts_items'
    );
    
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
    
    // prevede mime na fontawesome ikonu
    public function font_awesome_mime_icon( $mime_type ) {
        // definice znamych typu
      static $font_awesome_file_icon_classes = array(
        // Images
        'image' => 'fa-file-image-o',
        // Audio
        'audio' => 'fa-file-audio-o',
        // Video
        'video' => 'fa-file-video-o',
        // Documents
        'application/pdf' => 'fa-file-pdf-o',
        'application/msword' => 'fa-file-word-o',
        'application/vnd.ms-word' => 'fa-file-word-o',
        'application/vnd.oasis.opendocument.text' => 'fa-file-word-o',
        'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'fa-file-word-o',
        'application/vnd.ms-excel' => 'fa-file-excel-o',
        'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'fa-file-excel-o',
        'application/vnd.oasis.opendocument.spreadsheet' => 'fa-file-excel-o',
        'application/vnd.ms-powerpoint' => 'fa-file-powerpoint-o',
        'application/vnd.openxmlformats-officedocument.presentationml' => 'fa-file-powerpoint-o',
        'application/vnd.oasis.opendocument.presentation' => 'fa-file-powerpoint-o',
        'text/plain' => 'fa-file-text-o',
        'text/html' => 'fa-file-code-o',
        'application/json' => 'fa-file-code-o',
        // Archives
        'application/gzip' => 'fa-file-archive-o',
        'application/zip' => 'fa-file-archive-o',
        'application/x-zip-compressed' => 'fa-file-archive-o',
        // Misc
        'application/octet-stream' => 'fa-file-o',
      );
      
      // jestlize to tam mame cele
      if (isset($font_awesome_file_icon_classes[ $mime_type ])) {
        return $font_awesome_file_icon_classes[ $mime_type ];
      }
      else {    // jinak se podivame jestli mame aspon prvni cast
          $mime_parts = explode('/', $mime_type, 2);
          $mime_group = $mime_parts[0];
          if (isset($font_awesome_file_icon_classes[ $mime_group ])) {
            return $font_awesome_file_icon_classes[ $mime_group ];
          }
          else {
            return "fa-file-o"; // default na ktery spadne vse neurcene
          }
      }
    }
    
    public function human_readable_size($raw) {
        $size_names = array('Byte','KB','MB','GB','TB','PB','EB','ZB','YB','NB','DB');
        $name_id = 0;
        while ($raw>=1024 && ($name_id<count($size_names)-1)) {
            $raw /= 1024;
            $name_id++;
        }
        $ret = round($raw,1).' '.$size_names[$name_id];
        return $ret;
    }
    
}
