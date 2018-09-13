<?php

namespace Glued\Classes\Stor;

class Stor

{
    protected $container;
    
    public $app_dirs = array(
       "my_files"    => 'My private files',
       "my_owned"    => 'My owned files',
       "users"    => 'Users',
       "assets"    => 'Assets',
       "consumables"    => 'Consumables',
       "parts"    => 'Parts'
    );
    
    // prevod path na tabulku, kvuli predzjisteni prav
    // pozor, kazda z techto tabulek musi mit id nazvane c_uid a virtualni sloupec stor_name
    public $app_tables = array(
       "users"    => 't_users',
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
    
    // funkce na smazani souboru, vraci pole s tim co se stalo 
    public function delete_stor_file($link_id) {
        
        $data['success'] = false;
        $data['message'] = '';
        
        // nacteme si link a jeho sha512
        $this->container->db->where("c_uid", $link_id);
        $link_data = $this->container->db->getOne('t_stor_links');
        if ($this->container->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $data['success'] = false;
            $data['message'] = 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne';
        }
        else {
            $hash = $link_data['c_sha512'];
            
            // spocitame kolik mame linku s timto hasem
            $this->container->db->where("c_sha512", $hash);
            $links = $this->container->db->get('t_stor_links');
            
            //pokud mame jen jeden, smazeme i objekt
            if (count($links) == 1) {
                // nejdriv smazem z links
                $this->container->db->where("c_uid", $link_id);
                if ($this->container->db->delete('t_stor_links')) {
                    // nacteme si z object cestu ke smazani souboru, i kdz, sla by odvodit, ale muze tam byt prave jiny driver a pak cesta neni dana hashem, TODO
                    // zatim predpokladame driver fs, [0] znamena prvni prvek pole storage, coz je objekt takze za tim zase zaciname teckou
                    // rawQuery v joshcam vraci vzdy pole, i kdyz je vysledek jen jeden
                    $objects = $this->container->db->rawQuery(" SELECT `doc`->>'$.data.storage[0].path' AS path FROM t_stor_objects WHERE sha512 = ? ", Array ($hash));
                    // TODO, kontrola jestli je jeden vysledek a jestli neni path prazdna
                    $file_to_delete = $objects[0]['path'].'/'.$hash;
                    unlink($file_to_delete);
                    // mazani z objects
                    $this->container->db->where("sha512", $hash);
                    if ($this->container->db->delete('t_stor_objects')) {
                        $data['success'] = true;
                        $data['message'] = 'soubor '.$file_to_delete.' byl komplet smazan z links i object.';
                    }
                    else {
                        // tady je jen polovicni success
                        $data['success'] = true;
                        $data['message'] = 'soubor '.$file_to_delete.' byl smazan z links, ale zrejme nejakou systemovou chybou zustal v objects a neodkazuje ted na nej zadny link.';
                    }
                }
                else {
                    $data['success'] = false;
                    $data['message'] = 'smazani se nepovedlo';
                }
            }
            else if (count($links) > 1) {
                $this->container->db->where("c_uid", $link_id);
                if ($this->container->db->delete('t_stor_links')) {
                    $data['success'] = true;
                    $data['message'] = 'link na soubor byl smazan, ale bylo jich vic, takze soubor zustava';
                }
                else {
                    $data['success'] = false;
                    $data['message'] = 'smazani se nepovedlo';
                }
            }
            else {
                $data['success'] = false;
                $data['message'] = 'hash souboru neexistuje, zahadna chyba';
            }
        }
        
        return $data;
    }
    
}
