<?php

namespace Glued\Controllers\Parts;
use Glued\Controllers\Controller;

class PartsController extends Controller
{
    
    // shows basic page with all consumables
    public function gui($request, $response)
    {
        $costs_output = '';
        $sloupce = array("c_uid", "c_data->>'$.data.category' as type", "c_data->>'$.data.item_name' as name", "c_data->>'$.data.item_no' as item_no", "c_data->>'$.data.locations[*].stock' as stock");
        $this->container->db->orderBy("c_uid","asc");
        $items = $this->container->db->get('t_parts_items', null, $sloupce);
        if (count($items) > 0) {
            foreach ($items as $data) {
                if (empty($data['stock'])) { $stock = 'undefined'; }
                else {
                    $stock = array_sum(json_decode($data['stock'], true));
                }
                $costs_output .= '
                    <tr>
                        <th scope="row">'.$data['c_uid'].'</th>
                        <td>'.$data['type'].'</td>
                        <td>'.$data['name'].'</td>
                        <td>'.$data['item_no'].'</td>
                        <td>'.$stock.'</td>
                        <td><a href="'.$this->container->router->pathFor('parts.editform', ['id' => $data['c_uid']]).'">edit</a></td>
                    </tr>
                ';
            }
        }
        else {
            $costs_output = 'zatim zadne nejsou vlozeny';
        }
        
        /*
        $additional_javascript = '
    <script>
    function delete_cost(cost_id) {
        if (confirm("do you really want to delete this cost?")) {
            $.ajax({
              url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('accounting.api.delete').'" + cost_id,
              dataType: "text",
              type: "DELETE",
              data: "voiddata=1",
              success: function(data) {
                $("#cost_row_" + cost_id).remove();
              },
              error: function(xhr, status, err) {
                alert("ERROR: xhr status: " + xhr.status + ", status: " + status + ", err: " + err);
              }
            });
        }
    }
    </script>
        ';
        */
        return $this->container->view->render($response, 'parts/gui.twig', array(
            'costs_output' => $costs_output,
            'additional_javascript' => $additional_javascript,
            'ui_menu_active' => 'parts'
        ));
    }
    
    
    // show form for add new cost
    public function addForm($request, $response)
    {
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/form_ui.json');
        
        // schema celeho formulare
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/parts.json');
        
        // zakladni data, momentalne nic, ale musi tam byt aspon prazdny json objekt, tedy ty slozene zavorky
        $json_formdata_output = '{"data":{"ts_created":"'.time().'","ts_updated":"'.time().'"}}';
        
        //$this->container['settings']['glued']['hostname']
        //$this->container->settings->glued->hostname
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('parts.api.new').'",
      dataType: "text",
      type: "POST",
      data: "stockdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        // diky replacu nezustava puvodni adresa v historii, takze se to vice blizi redirectu
        // presmerovani na editacni stranku se vraci z toho ajaxu
        window.location.replace(data);
      },
      error: function(xhr, status, err) {
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        return $this->container->view->render($response, 'parts/add.twig', array(
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output
        ));
    }
    
    
    // show short form for add new assets
    public function addQuickForm($request, $response)
    {
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/form_ui_quick.json');
        
        // schema celeho formulare
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/parts.json');
        
        // zakladni data, momentalne nic, ale musi tam byt aspon prazdny json objekt, tedy ty slozene zavorky
        $json_formdata_output = '{"data":{"ts_created":"'.time().'","ts_updated":"'.time().'"}}';
        
        //$this->container['settings']['glued']['hostname']
        //$this->container->settings->glued->hostname
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('parts.api.new').'",
      dataType: "text",
      type: "POST",
      data: "stockdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        // diky replacu nezustava puvodni adresa v historii, takze se to vice blizi redirectu
        // presmerovani na editacni stranku se vraci z toho ajaxu
        window.location.replace(data);
      },
      error: function(xhr, status, err) {
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        return $this->container->view->render($response, 'parts/add.twig', array(
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output
        ));
    }
    
    
    // show form for edit existing cost
    public function editForm($request, $response, $args)
    {
        // zjistime jestli mame write pravo na tento parts
        if ($this->container->permissions->have_action_on_object('t_parts_items', $args['id'], 'write')) {
            
            $this->container->db->where("c_uid", $args['id']);
            $data = $this->container->db->getOne('t_parts_items');
            
            $upload_adresa = $this->container->router->pathFor('parts.upload', ['id' => $args['id']]);
            $reload_adresa = $this->container->router->pathFor('parts.editform', ['id' => $args['id']]);
            
            $vystup_obrazku = '';
            $sloupce = array("lin.c_uid", "lin.c_owner", "lin.c_filename", "obj.sha512", "obj.doc->>'$.data.size' as size", "obj.doc->>'$.data.mime' as mime", "obj.doc->>'$.data.ts_created' as ts_created");
            $this->container->db->join("t_stor_objects obj", "obj.sha512=lin.c_sha512", "LEFT");
            $this->container->db->where("c_inherit_table", "t_parts_items");
            $this->container->db->where("c_inherit_object", $args['id']);
            $this->container->db->orderBy("lin.c_filename","asc");
            $files = $this->container->db->get('t_stor_links lin', null, $sloupce);
            if (count($files) > 0) {
                foreach ($files as $filedata) {
                    $adresa = $this->container->router->pathFor('stor.serve.file', ['id' => $filedata['c_uid'], 'filename' => $filedata['c_filename']]);
                    $vystup_obrazku .= '
                    <div>
                        <a href="'.$adresa.'" class="">
                            '.(in_array($filedata['mime'], array('image/jpeg', 'image/png'))?'<img src="'.$adresa.'" width="300" />':'').'<br />
                            '.$filedata['c_filename'].'
                            <a class="remove" href="#" data-toggle="modal" data-target="#confirm-modal" onclick="$(\'#file_uid\').val('.$filedata['c_uid'].');">
                                <i class="fa fa-trash-o "></i>
                            </a>
                        </a>
                    </div>
                    ';
                }
            }
            
            
            // zvlastni pravidla pro vygenerovani jednotlivych prvku
            // odebrano   "required" : [ "wovat", "vat" ],
            $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/form_ui.json');
            
            // schema celeho editacniho formulare. je to prakticky shodne schema jako formular pro novy bill, krome title
            $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/parts.json');
            
            // zakladni data pro editaci
            $json_formdata_output = $data['c_data'];
            
            // vnitrek onsubmit funkce
            $json_onsubmit_output = '
        $.ajax({
          url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('parts.api.edit', ['id' => $args['id']]).'",
          dataType: "text",
          type: "PUT",
          data: "stockdata=" + JSON.stringify(formData.formData),
          success: function(data) {
            // diky replacu nezustava puvodni adresa v historii, takze se to vice blizi redirectu
            window.location.replace("https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('parts.gui').'");
          },
          error: function(xhr, status, err) {
            ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                     document.getElementById("main"));
          }
        });
            ';
            
            
            return $this->container->view->render($response, 'parts/edit.twig', array(
                'json_schema_output' => $json_schema_output,
                'json_uischema_output' => $json_uischema_output,
                'json_formdata_output' => $json_formdata_output,
                'json_onsubmit_output' => $json_onsubmit_output,
                'cost_id' => $args['id'], 'upload_adresa' => $upload_adresa, 'reload_adresa' => $reload_adresa, 'vystup_obrazku' => $vystup_obrazku
            ));
        }
        else {
            return $this->container->view->render($response, 'forbidden.twig');
        }
    }
    
    
    // funkce co zpracuje poslany nahravany soubor
    public function uploaderSave($request, $response, $args)
    {
        $item_id = $args['id'];
        $povolene_typy = array('photo', 'storage', 'warranty_card', 'service_protocol', 'invoice', 'manual');
        
        if (isset($args['name']) and in_array($args['name'], $povolene_typy)) {
            $beginning_name = $args['name'];
        }
        else {
            $beginning_name = 'photo';
        }
        
        $files = $request->getUploadedFiles();
        if (empty($files['webcam'])) {
            throw new Exception('Expected uploaded file, got none.');
        }
        
        $newfile = $files['webcam'];
        
        $actual_dir = 'parts';
        
        if (isset($this->container->stor->app_dirs[$actual_dir])) {
            
            if ($newfile->getError() === UPLOAD_ERR_OK) {
                //$filename = $newfile->getClientFilename();  // TODO, prejmenovat na photo_yymmdd_hhss.jpg
                if (isset($_POST['upload_formularem'])) { $filename = $_FILES['webcam']['name']; }   // prislo z formulare
                else { $filename = $beginning_name.'_'.date('Ymd_His').'.jpg'; }    // prislo z kamery
                $sha512 = hash_file('sha512', $_FILES['webcam']['tmp_name']);
                
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
                    $this->container->db->insert('t_stor_objects', $data);
                    
                    // vlozime do links
                    $data = Array (
                    "c_sha512" => $sha512,
                    "c_owner" => $_SESSION['user_id'],
                    "c_filename" => $filename,
                    "c_inherit_table" => "t_parts_items",
                    "c_inherit_object" => $item_id
                    );
                    $this->container->db->insert ('t_stor_links', $data);
                    
                    $this->container->flash->addMessage('info', 'Your file ('.$filename.') was uploaded successfully.');
                }
                else {
                    // soubor uz existuje v objects ale vlozime ho aspon do links
                    $data = Array (
                    "c_sha512" => $sha512,
                    "c_filename" => $filename,
                    "c_inherit_table" => "t_parts_items",
                    "c_inherit_object" => $item_id
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
        
        if (isset($_POST['upload_formularem'])) {
            return $response->withRedirect($this->container->router->pathFor('parts.editform', ['id' => $item_id]));
        }
        else {
            // vratime prosty text
           $response->getBody()->write('ok');
           return $response;
        }
    }
    
    
    
}
