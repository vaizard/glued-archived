<?php

namespace Glued\Controllers\Stor;
use Glued\Controllers\Controller;
use Glued\Classes\Stor;
use Glued\Classes\Auth;

class StorController extends Controller
{
    
    // uploader with simple browser
    
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
            'stor_dirs_options' => $stor_dirs_options,
            'ui_menu_active' => 'stor.uploader'
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
        $upload_type = $request->getParam('upload_type');
        
        // vyjimka na my_files
        if ($raw_path == 'my_files') {
            $actual_dir = 'users';
            $actual_object = $_SESSION['user_id'];
        }
        else {
            $parts = explode('/', $raw_path);
            if (count($parts) > 1) {
                $actual_dir = $parts[0];
                $actual_object = $parts[1];
            }
            else {
                $actual_dir = '';   // pokud to neni objekt v diru, tak delame jako ze dir neexistuje.
            }
        }
        
        // pokud dir existuje v seznamu povolenych diru, uploadujem (ovsem je zadany timpadem i objekt)
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
                    "c_filename" => $filename,
                    "c_inherit_table" => $this->container->stor->app_tables[$actual_dir],
                    "c_inherit_object" => $actual_object
                    );
                    $this->container->db->insert ('t_stor_links', $data);
                    
                    $this->container->flash->addMessage('info', 'Your file ('.$filename.') was uploaded successfully.');
                }
                else {
                    // soubor uz existuje v objects ale vlozime ho aspon do links
                    $data = Array (
                    "c_sha512" => $sha512,
                    "c_owner" => $_SESSION['user_id'],
                    "c_filename" => $filename,
                    "c_inherit_table" => $this->container->stor->app_tables[$actual_dir],
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
        
        if ($upload_type == 'browser') {
            $redirect_url = $this->container->router->pathFor('stor.browser').'?filter=/'.$actual_dir.'/'.$actual_object;
        }
        else if ($upload_type == 'general') {   // obecny form nekde jinde mimo stor, posle si vlastni zpatecni adresu
            $redirect_url = $request->getParam('return_url');
        }
        else {
            if (!empty($actual_dir)) {
                $redirect_url = $this->container->router->pathFor('stor.uploader').'/~/'.$raw_path;
            }
            else {
                $redirect_url = $this->container->router->pathFor('stor.uploader');
            }
        }
        
        return $response->withRedirect($redirect_url);
    }
    
    // funkce pro post smazani linku (a pokud je posledni tak i objektu)
    public function uploaderDelete($request, $response)
    {
        $link_id = (int) $request->getParam('file_uid');
        $actual_delete_dir = $request->getParam('actual_delete_dir');
        $return_uri = $request->getParam('return_uri');
        
        $returned_data = $this->container->stor->delete_stor_file($link_id);
        
        if ($returned_data['success']) {
            $this->container->flash->addMessage('info', $returned_data['message']);
        }
        else {
            $this->container->flash->addMessage('error', $returned_data['message']);
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
        $actual_dir = $request->getParam('actual_dir'); // jen v uploaderu
        $action_type = $request->getParam('action_type');
        $target_dir = $request->getParam('target_dir');
        $target_object_id = $request->getParam('target_object_id');
        $set_new_owner = (int) $request->getParam('set_new_owner'); // 1 - system, 2 - prihlaseny, 3 - nemenit
        $action_source = $request->getParam('action_source');   // jen v browseru
        
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
            
            // urceni ownera
            if ($set_new_owner == 1) {  // system select
                // pokud presunuju nebo kopiruju do private users, mel by byt owner vzdy ten user
                if ($target_dir == 'users') {
                    $new_owner = $target_object_id;
                }
                else {  // pokud je cil nejaky modul, tak u copy bych mel byt owner ja, a u move bud nemenit nebo ja
                    $new_owner = $_SESSION['user_id'];
                }
            }
            else if ($set_new_owner == 2) { $new_owner = $_SESSION['user_id']; }    // vzdy ja
            else if ($set_new_owner == 3) { $new_owner = $link_data['c_owner']; }   // nemenit
            
            if ($action_type == 'copy') {
                if (in_array('read', $allowed_global_actions) and in_array('write', $allowed_global_target_actions)) {
                    $data = Array (
                    "c_sha512" => $link_data['c_sha512'],
                    "c_owner" => $new_owner,
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
                        'c_owner' => $new_owner,
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
        
        if ($action_source == 'browser') {
            $redirect_url = $this->container->router->pathFor('stor.browser').'?filter=/'.$target_dir.'/'.$target_object_id;
        }
        else {
            // toto by melo byt vzdy nastaveno pri editaci, abychom mohli tu adresu zase vykreslit s uz zmenenym nazvem
            if (!empty($actual_dir)) {
                $redirect_url = $this->container->router->pathFor('stor.uploader').'/~/'.$actual_dir;
            }
            else {  // pro jistotu, kdyz to nebude nastaveno, jdeme na root
                $redirect_url = $this->container->router->pathFor('stor.uploader');
            }
        }
        
        return $response->withRedirect($redirect_url);
    }
    
    
    /* browser with a filter */
    
    // vypis stranky s filtracnim browserem souboru, select2
    public function storBrowserGui($request, $response, $args)
    {
        $vystup = '';
        $preset_options = '';
        
        $preset_filter = $request->getParam('filter');
        
        // pokud je v getu nejaky filter
        if (!empty($preset_filter)) {
            $casti_filtru = explode(' ', $preset_filter);
            foreach ($casti_filtru as $filter) {
                $safe_filter = trim($filter);
                if (!empty($filter)) {
                    $preset_options .= '<option value="'.$safe_filter.'" selected>'.$safe_filter.'</option>';
                }
            }
        }
        
        $additional_javascript = '
    <script>
    
    // tuto funkci pouzijeme jen v pripade ze chceme ukladat historii a menit adresu. tj treba ne, kdyz jdeme back a forward a ne kdyz treba uploadujeme
    function push_filter_state() {
        var vybrano = $("#stor-files-select2-filter").val() || [];
        var url_filter = vybrano.join(" ");
        // prepiseme adresu (strcime tam objekt s obsahem selectu2)
        if (typeof (history.pushState) != "undefined") {
            if (url_filter == "") {
                var new_url = "'.$this->container->router->pathFor('stor.browser').'";
            }
            else {
                var new_url = "'.$this->container->router->pathFor('stor.browser').'?filter=" + url_filter;
            }
            history.pushState(vybrano, "", new_url);
        }
    }
    
    function filter_stor_files(orderby, direction, page) {
        var vybrano = $("#stor-files-select2-filter").val() || [];
        var upraveno = JSON.stringify(vybrano);
        var url_filter = vybrano.join(" ");
        
        // osetrime, kdyz nebyly parametry
        if (arguments.length === 0) {
            var orderby = "name";
            var direction = "asc";
            var page = 1;
        }
        
        // ted to posleme jako zestringovany json
        
        $.ajax({
          url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('stor.api.filtered.files').'",
          type: "GET",
          dataType: "text",
          data: { filters: upraveno, orderby: orderby, direction: direction, page: page },
          success: function(data) {
            //alert(data);
            $("#stor-files-output").html(data);
            
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
            
            // obecna zkratka na 1 filtr. id a text jsou v atribtu data
            $(".stor-shortcuts").on("click", function(e){
                // preventujem link
                e.preventDefault();
                // smazeme co tam aktualne je vybrane a nevyvolame event change
                $("#stor-files-select2-filter").val(null);
                // nacteme si id a text z data
                var short_id = $(this).data("id");
                var short_text = $(this).data("text");
                
                if (short_id != "") {
                    // option se ma pridat, jen pokud uz tam neni, a vyvolame event change
                    if ($("#stor-files-select2-filter").find("option[value=\'" + short_id + "\']").length) {
                        $("#stor-files-select2-filter").val(short_id).trigger("change");
                    } else {
                        var option = new Option(short_text, short_id, false, true);
                        $("#stor-files-select2-filter").append(option).trigger("change");
                    }
                    
                    $("#stor-files-select2-filter").trigger({
                        type: "select2:select",
                        params: {
                            data: {"id":short_id, "text":short_text}
                        }
                    });
                }
                else {
                    $("#stor-files-select2-filter").trigger("change");
                }
            });
            
            // dalsi zkratky- pokud by bylo treba nastavit vice filtru naraz, zatim neni treba
            
          },
          error: function(xhr, status, err) {
            alert("ERROR: xhr status: " + xhr.status + ", status: " + status + ", err: " + err);
          }
        });
    }
    
    // funkce, kterou smazeme soubor ajaxem a obnovime vypis souboru podle zadaneho filtru
    function delete_stor_file_ajax() {
        var link_id = $("#delete_file_uid").val();
        $.ajax({
          url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('stor.ajax.delete').'",
          type: "POST",
          dataType: "text",
          data: { link_id: link_id },
          success: function(data) {
            // vyhodi nekde hlasku
            
            filter_stor_files();
          }
        });
    }
    
    // funkce, kterou zeditujeme nazev souboru ajaxem a obnovime vypis souboru podle zadaneho filtru
    function edit_stor_file_ajax() {
        var link_id = $("#edit_file_uid").val();
        var new_fname = $("#edit_file_fname").val();
        $.ajax({
          url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('stor.ajax.update').'",
          type: "POST",
          dataType: "text",
          data: { link_id: link_id, new_fname: new_fname },
          success: function(data) {
            // vyhodi nekde hlasku
            
            filter_stor_files();
          }
        });
    }
    
    // cte existujici objekty do modalu pro copy move
    function read_modal_objects() {
        // zjistime si ktery cilovy dir je vybrany v selectu
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
    
    // inicializujeme select 2 a tlacitko filtr
    $(document).ready(function() {
        
        $("#stor-files-select2-filter").select2({
          tags: true,
          tokenSeparators: [" "],
          minimumInputLength: 1,
          createTag: function (params) {
            // nevytvaret kdyz to zacina /, #, @
            if (params.term.indexOf("/") === 0) {
              return null;
            }
            if (params.term.indexOf("@") === 0) {
              return null;
            }
            if (params.term.indexOf("#") === 0) {
              return null;
            }
            
            return {
              id: params.term,
              text: params.term
            }
          },
          width: "100%",
          ajax: {
            url: "'.$this->container->router->pathFor('stor.api.filter.options').'",
            dataType: "json"
          }
        });
        
        $("#stor-files-filter-button").on("click", function(){
            filter_stor_files();
            push_filter_state();
        });
        
        // omezime push, aby se nedelal, kdyz probiha popstate (back, forward button)
        $("#stor-files-select2-filter").on("change", function(e, delej_push){
            filter_stor_files();
            if (delej_push != "nepush") { push_filter_state(); }
        });
        
        // zavolame to defaultne na prazdny filtr
        filter_stor_files();
        // iniciujeme prvotni objekty v copy move modalu
        read_modal_objects();
        
    });
    
    // zachytime back button v browseru, pro pushnute adresy v historii
    $(window).on("popstate", function(e) {
        if (e.originalEvent.state !== null) {
            // nacpeme ten stav to do selectu 2 (e.originalEvent.state obsahuje klice selectu oddelene carkou)
            // protoze jsme stale na stejne strance, ty optiony uz tam jsou
            var select2klice = e.originalEvent.state.toString().split(",");
            $("#stor-files-select2-filter").val(select2klice).trigger("change", "nepush");
        }
        else {
            $("#stor-files-select2-filter").val(null).trigger("change", "nepush");
        }
    });
    
    </script>
        ';
        
        // priprava vyberu diru do copy move popupu
        $stor_dirs_options = '';
        foreach ($this->container->stor->app_dirs as $dir => $description) {
            if ($dir == 'my_owned' or $dir == 'my_files') { continue; }
            $stor_dirs_options .= '<option value="'.$dir.'">'.$description.'</option>';
        }
        
        return $this->container->view->render($response, 'stor/stor-browser-gui.twig',
        array(
            'vystup' => $vystup,
            'preset_options' => $preset_options,
            'article_class' => 'items-list-page',
            'additional_javascript' => $additional_javascript,
            'stor_dirs_options' => $stor_dirs_options,
            'ui_menu_active' => 'stor.browser'
        ));
    }
    
    
}
