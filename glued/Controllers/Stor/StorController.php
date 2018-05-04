<?php

namespace Glued\Controllers\Stor;
use Glued\Controllers\Controller;
use Glued\Classes\Stor;
use Glued\Classes\Auth;

class StorController extends Controller
{
    
    // fukce co vypise prehled nahranych a formular pro nahrani dalsiho
    public function storUploadGui($request, $response, $args)
    {
        $vystup = '';
        
        $actual_dirname = '';
        if (!empty($args['dir'])) {
            if (!empty($args['oid'])) {
                $actual_dirname = $args['dir'].'/'.$args['oid'];
            }
            else {
                $actual_dirname = $args['dir'];
            }
        }
        
        // priprava vyberu diru do copy move popupu
        $stor_dirs_options = '';
        foreach ($this->container->stor->app_dirs as $dir => $description) {
            if ($dir == 'my_owned' or $dir == 'my_files') { continue; }
            $stor_dirs_options .= '<option value="'.$dir.'">'.$description.'</option>';
        }
        
        $additional_javascript = '
    <script>
    
    var actual_dirname = "'.$actual_dirname.'";
    
    // definice funkce
    function show_files(dirname, can_upload) {
        $.ajax({
          url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('stor.api.files').'",
          dataType: "text",
          type: "GET",
          data: "dirname=" + dirname,
          success: function(data) {
            $("#stor-files-output").html(data);
            
            // prepneme form do uploadovaciho nebo zakazaneho stavu
            if (can_upload) {
                $("#can_upload_button").show();
                $("#cannot_upload_message").hide();
            }
            else {
                $("#can_upload_button").hide();
                $("#cannot_upload_message").show();
            }
            
            // nastavime prepnuty dir do uploadovaciho a mazaciho formu (a dalsich formu), vsechny kontejnery maji stejnou class
            $(".stor_hidden_actual_dir").val(dirname);
            /*
            $("#actual_dir").val(dirname);
            $("#actual_delete_dir").val(dirname);
            $("#stor_edit_form_actual_dir").val(dirname);
            $("#stor_copy_move_form_actual_dir").val(dirname);
            */
            
            // musime znova inicializovat rozklikavaci ozubena kola na konci radku, coz se normalne dela v app.js pri nacteni stranky
            var $itemActions = $(".item-actions-dropdown");
            $(document).on("click",function(e) {
                if (!$(e.target).closest(".item-actions-dropdown").length) {
                    $itemActions.removeClass("active");
                }
            });
            $(".item-actions-toggle-btn").on("click",function(e){
                e.preventDefault();
                var $thisActionList = $(this).closest(".item-actions-dropdown");
                $itemActions.not($thisActionList).removeClass("active");
                $thisActionList.toggleClass("active");
            });
            
            // zmenime adresu
            if (typeof (history.pushState) != "undefined") {
                if (dirname == "") {
                    var obj = { Title: "ugo", Url: "'.$this->container->router->pathFor('stor.uploader').'" };
                }
                else {
                    var obj = { Title: "ugo", Url: "'.$this->container->router->pathFor('stor.uploader').'/~/'.'" + dirname };
                }
                history.pushState(obj, obj.Title, obj.Url);
            }
            
          },
          error: function(xhr, status, err) {
            alert("ERROR: xhr status: " + xhr.status + ", status: " + status + ", err: " + err);
          }
        });
    }
    
    // cte existujici objekty do modalu pro copy move
    function read_modal_objects() {
        // zjistime si ktery dir je vybrany
        
        var dirname = $("#stor_copy_move_target_dir").val();
        
        $.ajax({
          url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('stor.api.modal.objects').'",
          dataType: "text",
          type: "GET",
          data: "dirname=" + dirname,
          success: function(data) {
            $("#stor_copy_move_target_object_id").html(data);
          },
          error: function(xhr, status, err) {
            alert("ERROR: xhr status: " + xhr.status + ", status: " + status + ", err: " + err);
          }
        });
    }
    
    // na zacatku to zavolame se stor parametrem (mozna az po nahrani cele stranky)
    $(document).ready(function() {
        show_files(actual_dirname, false);
        read_modal_objects();
    });
    
    </script>
        ';
        
        return $this->container->view->render($response, 'stor-upload-gui.twig',
        array(
            'vystup' => $vystup,
            'article_class' => 'items-list-page',
            'additional_javascript' => $additional_javascript,
            'stor_dirs_options' => $stor_dirs_options
        ));
    }
    
    
    // funkce co zpracuje poslany nahravany soubor
    public function uploaderSave($request, $response)
    {
        $files = $request->getUploadedFiles();
        if (empty($files['file'])) {
            throw new Exception('Expected uploaded file, got none.');
        }
        
        $newfile = $files['file'];
        
        $raw_path = $request->getParam('actual_dir');
        
        // vyjimka na my_files
        if ($raw_path == 'my_files') {
            $actual_dir = 'users';
            $actual_object = $_SESSION['authentication_id'];
        }
        else {
            $parts = explode('/', $raw_path);
            if (count($parts) > 1) {
                $actual_dir = $parts[0];
                $actual_object = $parts[1];
            }
            else {
                $actual_dir = '';
            }
        }
        
        // pokud dir existuje v seznamu povolenych diru, uploadujem
        if (isset($this->container->stor->app_dirs[$actual_dir])) {
            
            if ($newfile->getError() === UPLOAD_ERR_OK) {
                $filename = $newfile->getClientFilename();
                $sha512 = hash_file('sha512', $_FILES['file']['tmp_name']);
                
                // zjistime jestli soubor se stejnym hashem uz mame
                $this->container->db->where("sha512", $sha512);
                $this->container->db->getOne('t_stor_objects');
                if ($this->container->db->count == 0) {
                    
                    // vytvorime tomu adresar
                    $dir1 = substr($sha512, 0, 1);
                    $dir2 = substr($sha512, 1, 1);
                    $dir3 = substr($sha512, 2, 1);
                    $dir4 = substr($sha512, 3, 1);
                    
                    $cilovy_dir = '../private/stor/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.$dir4;
                    
                    if (!is_dir($cilovy_dir)) { mkdir($cilovy_dir, 0777, true); }
                    
                    // presuneme
                    // $full_path = "/var/www/html/glued/private/";
                    $newfile->moveTo($cilovy_dir.'/'.$sha512);
                    
                    // pokud ne, vlozime
                    $new_file_array = array();
                    $new_file_array['_v'] = '1';
                    $new_file_array['sha512'] = $sha512;
                    $new_file_array['size'] = $newfile->getSize();
                    $new_file_array['mime'] = $newfile->getClientMediaType();
                    $new_file_array['checked'] = false;
                    $new_file_array['ts_created'] = time();
                    $new_file_array['storage'] = array(array("driver" => "fs", "path" => $cilovy_dir));
                    
                    $new_data_array = array();
                    $new_data_array['data'] = $new_file_array;
                    
                    $json_string = json_encode($new_data_array);
                    
                    // pozor, spojit dve vkladani pres commit, TODO
                    
                    // vlozime do objects
                    $data = Array ("doc" => $json_string);
                    $this->container->db->insert ('t_stor_objects', $data);
                    
                    // vlozime do links
                    $data = Array (
                    "c_sha512" => $sha512,
                    "c_owner" => $_SESSION['user_id'],
                    "c_path" => $actual_dir."/".$actual_object,
                    "c_filename" => $filename,
                    "c_inherit_table" => $this->container->stor->app_dirs[$app_tables],
                    "c_inherit_object" => $actual_object
                    );
                    $this->container->db->insert ('t_stor_links', $data);
                    
                    $this->container->flash->addMessage('info', 'Your file ('.$filename.') was uploaded successfully.');
                }
                else {
                    // soubor uz existuje v objects ale vlozime ho aspon do links
                    $data = Array (
                    "c_sha512" => $sha512,
                    "c_path" => $actual_dir."/".$actual_object,
                    "c_filename" => $filename,
                    "c_inherit_table" => $this->container->stor->app_dirs[$app_tables],
                    "c_inherit_object" => $actual_object
                    );
                    $this->container->db->insert ('t_stor_links', $data);
                    
                    $this->container->flash->addMessage('info', 'Your file ('.$filename.') was uploaded successfully as link. Its hash already exists in objects table.');
                }
            }
            else {
                $this->container->flash->addMessage('error', 'your file failed to upload.');
            }
        }
        else {
            $this->container->flash->addMessage('error', 'your cannot upload into this dir.');
        }
        
        if (!empty($actual_dir)) {
            $redirect_url = $this->container->router->pathFor('stor.uploader').'/~/'.$raw_path;
        }
        else {
            $redirect_url = $this->container->router->pathFor('stor.uploader');
        }
        
        return $response->withRedirect($redirect_url);
    }
    
    // funkce pro post test smazani linku (a pokud je posledni tak i objektu)
    public function uploaderDelete($request, $response)
    {
        $link_id = (int) $request->getParam('file_uid');
        $actual_delete_dir = $request->getParam('actual_delete_dir');
        $return_uri = $request->getParam('return_uri');
        
        // nacteme si link a jeho sha512
        $this->container->db->where("c_uid", $link_id);
        $link_data = $this->container->db->getOne('t_stor_links');
        if ($this->container->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $this->container->flash->addMessage('error', 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne');
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
                        $this->container->flash->addMessage('info', 'soubor '.$file_to_delete.' byl komplet smazan z links i object.');
                    }
                    else {
                        $this->container->flash->addMessage('info', 'soubor '.$file_to_delete.' byl smazan z links, ale zrejme nejakou systemovou chybou zustal v objects a neodkazuje ted na nej zadny link.');
                    }
                }
                else {
                    $this->container->flash->addMessage('error', 'smazani se nepovedlo');
                }
            }
            else if (count($links) > 1) {
                $this->container->db->where("c_uid", $link_id);
                if ($this->container->db->delete('t_stor_links')) {
                    $this->container->flash->addMessage('info', 'link na soubor byl smazan, ale bylo jich vic, takze soubor zustava');
                }
                else {
                    $this->container->flash->addMessage('error', 'smazani se nepovedlo');
                }
            }
            else {
                $this->container->flash->addMessage('error', 'hash souboru neexistuje, zahadna chyba');
            }
        }
        
        if (!empty($return_uri)) {  // pokud mazeme z jineho mista, a chceme se tam pak vratit, je v post promennych return_uri
            $redirect_url = $return_uri;
        }
        else if (!empty($actual_delete_dir)) {
            $redirect_url = $this->container->router->pathFor('stor.uploader').'/~/'.$actual_delete_dir;
        }
        else {
            $redirect_url = $this->container->router->pathFor('stor.uploader');
        }
        
        return $response->withRedirect($redirect_url);
    }
    
    // zobrazovac nebo vynucovac stazeni
    public function serveFile($request, $response, $args)
    {
        // parametr id identifikuje link
        $link_id = $args['id'];
        
        // nacteme sha512
        $this->container->db->where ("c_uid", $link_id);
        $file_link = $this->container->db->getOne("t_stor_links");
        
        // nacteme mime
        $sloupce = array("doc->>'$.data.mime' as mime", "doc->>'$.data.storage[0].path' as path");
        $this->container->db->where("sha512", $file_link['c_sha512']);
        $file_data = $this->container->db->getOne("t_stor_objects", $sloupce);
        
        // path mame v takovem nejakem tvaru
        // ../private/stor/0/2/8/0
        $fullpath = $file_data['path'].'/'.$file_link['c_sha512'];
        
        /*
        $vystup = '<div>vypisuji soubor na adrese '.$fullpath.'</div>';
        $vystup .= '<div>nacteno z db: '.print_r($file_data, true).'</div>';
        
        return $this->container->view->render($response, 'stor-obecny-vystup.twig', array('vystup' => $vystup));
        */
        
        header('Content-Type: '.$file_data['mime']);
        readfile($fullpath);    // taky vlastne nevim jestli to takto vypsat
        exit(); // ? nevim nevim
        
    }
    
    // update nazvu z popupoveho formu
    public function uploaderUpdate($request, $response)
    {
        $link_id = (int) $request->getParam('file_id');
        $actual_dir = $request->getParam('actual_dir');
        //$return_uri = $request->getParam('return_uri');
        
        // nacteme si link
        $this->container->db->where("c_uid", $link_id);
        $link_data = $this->container->db->getOne('t_stor_links');
        if ($this->container->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $this->container->flash->addMessage('error', 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne');
        }
        else {
            // pokud mame prava na tento objekt
            if ($this->container->permissions->have_action_on_object($link_data['c_inherit_table'], $link_data['c_inherit_object'], 'write')) {
                // zmenime nazev na novy
                $data = Array (
                    'c_filename' => $request->getParam('new_filename')
                );
                $this->container->db->where("c_uid", $link_id);
                if ($this->container->db->update('t_stor_links', $data)) {
                    $this->container->flash->addMessage('info', 'soubor byl prejmenovan');
                }
                else {
                    $this->container->flash->addMessage('error', 'prejmenovani se nepovedlo');
                }
            }
            else {
                $this->container->flash->addMessage('error', 'k prejmenovani nemate prava');
            }
        }
        
        // toto by melo byt vzdy nastaveno pri editaci, abychom mohli tu adresu zase vykreslit s uz zmenenym nazvem
        if (!empty($actual_dir)) {
            $redirect_url = $this->container->router->pathFor('stor.uploader').'/~/'.$actual_dir;
        }
        else {  // pro jistotu, kdyz to nebude nastaveno, jdeme na root
            $redirect_url = $this->container->router->pathFor('stor.uploader');
        }
        
        return $response->withRedirect($redirect_url);
    }
    
    // copy nebo move
    public function uploaderCopyMove($request, $response)
    {
        $link_id = (int) $request->getParam('file_id');
        $actual_dir = $request->getParam('actual_dir');
        $action_type = $request->getParam('action_type');
        $target_dir = $request->getParam('target_dir');
        $target_object_id = $request->getParam('target_object_id');
        
        
        // nacteme si link
        $this->container->db->where("c_uid", $link_id);
        $link_data = $this->container->db->getOne('t_stor_links');
        if ($this->container->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $this->container->flash->addMessage('error', 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne');
        }
        else {
            // nacteme prava na tabulku, TODO, meli bychom ale nacist prava na ten konkretni objekt, coz neni vyladene zatim
            $allowed_global_actions = $this->container->permissions->read_global_privileges($link_data['c_inherit_table']);
            $allowed_global_target_actions = $this->container->permissions->read_global_privileges($this->container->stor->app_tables[$target_dir]);
            
            if ($action_type == 'copy') {
                if (in_array('read', $allowed_global_actions) and in_array('write', $allowed_global_target_actions)) {
                    $data = Array (
                    "c_sha512" => $link_data['c_sha512'],
                    "c_owner" => $_SESSION['user_id'],
                    "c_path" => $target_dir.'/'.$target_object_id,
                    "c_filename" => $link_data['c_filename'],
                    "c_inherit_table" => $this->container->stor->app_tables[$target_dir],
                    "c_inherit_object" => $target_object_id
                    );
                    if ($this->container->db->insert ('t_stor_links', $data)) {
                        $this->container->flash->addMessage('info', 'soubor byl zkopirovan');
                    }
                    else {
                        $this->container->flash->addMessage('error', 'kopirovani se nepovedlo');
                    }
                }
                else {
                    $this->container->flash->addMessage('error', 'ke kopirovani nemate prava');
                }
            }
            else if ($action_type == 'move') {
                if (in_array('write', $allowed_global_actions) and in_array('write', $allowed_global_target_actions)) {
                    $data = Array (
                        'c_path' => $target_dir.'/'.$target_object_id,
                        'c_inherit_table' => $this->container->stor->app_tables[$target_dir],
                        'c_inherit_object' => $target_object_id
                    );
                    $this->container->db->where("c_uid", $link_id);
                    if ($this->container->db->update('t_stor_links', $data)) {
                        $this->container->flash->addMessage('info', 'soubor byl presunut');
                    }
                    else {
                        $this->container->flash->addMessage('error', 'presunuti se nepovedlo');
                    }
                }
                else {
                    $this->container->flash->addMessage('error', 'k presunu nemate prava');
                }
            }
        }
        
        // toto by melo byt vzdy nastaveno pri editaci, abychom mohli tu adresu zase vykreslit s uz zmenenym nazvem
        if (!empty($actual_dir)) {
            $redirect_url = $this->container->router->pathFor('stor.uploader').'/~/'.$actual_dir;
        }
        else {  // pro jistotu, kdyz to nebude nastaveno, jdeme na root
            $redirect_url = $this->container->router->pathFor('stor.uploader');
        }
        
        return $response->withRedirect($redirect_url);
    }
    
}
