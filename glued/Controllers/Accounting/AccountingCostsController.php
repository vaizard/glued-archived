<?php
namespace Glued\Controllers\Accounting;

use Glued\Controllers\Controller;

class AccountingCostsController extends Controller
{
    
    // shows basic page with all costs
    public function getCosts($request, $response)
    {
        $costs_output = '';
        $sloupce = array("c_uid", "c_data", "c_data->>'$.data.inv_nr' as inv_nr", "c_data->>'$.data.supplier.name' as name", "c_data->>'$.data.acc_total_vat' as acc_total_vat", "c_data->>'$.data.acc_curr' as acc_curr");
        $this->container->db->orderBy("c_uid","asc");
        $bills = $this->container->db->get('t_accounting_received', null, $sloupce);
        if (count($bills) > 0) {
            foreach ($bills as $data) {
                // dekodujeme si data
                $json_data = json_decode($data['c_data'], true);
                // zjistime spojene concat managerial_accounting.project_name
                $project = '';
                $mana_groups = '';
                
                $project_pole = array();
                $project_names_pole = array();
                $mana_groups_pole = array();
                if (isset($json_data['data']['managerial_acc'])) {
                    $pole_accountu = $json_data['data']['managerial_acc'];
                    foreach ($pole_accountu as $ma) {
                        // pixel id je id z tabulky vektoru
                        $vektor_id = (int) $ma['pixel_id'];
                        $vector_sloupce = array("c_uid", "c_data->>'$.data.summary[0].data' as name");
                        $this->container->db->where("c_uid", $vektor_id);
                        $vector_data = $this->container->db->getOne('t_vectors', $vector_sloupce);
                        if ($this->container->db->count == 1) {
                            $project_names_pole[] = $vector_data['name'];
                            $project_pole[] = '<a href="'.$this->container->router->pathFor('vectors.vector', array('id' => $ma['pixel_id'])).'" title="'.$vector_data['name'].'">'.$ma['pixel_id'].'</a>';
                            $mana_groups_pole[] = $ma['acc_group'];
                        }
                    }
                    $project = implode(', ', $project_pole);
                    $mana_groups = implode(', ', $mana_groups_pole);
                }
                
                $osekane_note = $json_data['data']['note'];
                if (strlen($osekane_note) > 25) { $osekane_note = substr($osekane_note, 0, 25); }
                
                // slozeni ext
                $ext_pole = array();
                if (!empty($json_data['data']['ext_id'][0]['svc'])) { $ext_pole[] = $json_data['data']['ext_id'][0]['svc']; }
                if (!empty($json_data['data']['ext_id'][0]['id1'])) { $ext_pole[] = $json_data['data']['ext_id'][0]['id1']; }
                if (!empty($json_data['data']['ext_id'][0]['id2'])) { $ext_pole[] = $json_data['data']['ext_id'][0]['id2']; }
                if (count($ext_pole) > 0) { $ext_vystup = implode('/', $ext_pole); }
                else { $ext_vystup = ''; }
                
                $costs_output .= '
                    <tr id="cost_row_'.$data['c_uid'].'">
                        <th scope="row">'.$data['c_uid'].'</th>
                        <td>'.$data['inv_nr'].'</td>
                        <td>'.$data['name'].'</td>
                        <td>'.$data['acc_total_vat'].' '.$data['acc_curr'].'</td>
                        <td>'.$project.'</td>
                        <td>'.$mana_groups.'</td>
                        <td>'.$ext_vystup.'</td>
                        <td title="'.$json_data['data']['note'].'">'.$osekane_note.'</td>
                        <td>
                            <a href="'.$this->container->router->pathFor('accounting.editcostform').$data['c_uid'].'"><i class="fa fa-edit"></i></a>
                            <i class="fa fa-trash" style="cursor: pointer; color: red;" onclick="delete_cost('.$data['c_uid'].');"></i>
                        </td>
                    </tr>
                ';
            }
        }
        else {
            $costs_output .= '
                <tr>
                    <th scope="row"></th>
                    <td colspan="3">no bills at the moment</td>
                </tr>
            ';
        }
        
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
        
        // pripravim obsah modalu pro tabulku platby_mzdy
        $modal_acl_table = $this->container->permissions->modal_output_rights('accounting_accepted', 'table');
        $modal_acl_global = $this->container->permissions->modal_output_rights('accounting_accepted', 'global');
        
        // nacteme si mozne akce, TODO spis dat do ajaxu primo do formu
        $action_options = '';
        $akce = $this->container->db->get('t_action');
        if ($this->container->db->count > 0) {
            foreach ($akce as $akce1) {
                $action_options .= '<option value="'.$akce1['c_title'].'">'.$akce1['c_title'].' ('.($akce1['c_apply_object'] == 1?'object':'table').')</option>';
            }
        }
        
        return $this->container->view->render($response, 'accounting/costs.twig', array(
            'related_table' => 'accounting_accepted',
            'return_modal_form_uri' => $this->container->router->pathFor('accounting.costs'),
            'action_options' => $action_options,
            'modal_acl_table' => $modal_acl_table,
            'modal_acl_global' => $modal_acl_global,
            'costs_output' => $costs_output,
            'additional_javascript' => $additional_javascript,
            'ui_menu_active' => 'accounting.costs'
        ));
    }
    
    // show form for add new cost
    public function addCostForm($request, $response)
    {
        $form_output = '';
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/bill_form_ui.json');
        
        // schema celeho formulare pro novy zaznam
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/cost_columns.json');
        
        // zakladni data, jedna polozka procentualni dane
        $json_formdata_output = '
{
    "data":{
        "right_column": {
          "managerial_acc": [
            {}
          ],
          "financial_acc": [
            {}
          ]
        }
    }
}
        ';
        
        // pridame do schematu vlastni enum optiony
        $accounting_groups_array = '';
        $this->container->db->where("c_definition_id", 1);
        $this->container->db->orderBy("c_group_number","asc");
        $groups = $this->container->db->get('t_accounting_account_groups');
        if (count($groups) > 0) {
            foreach ($groups as $data) {
                $accounting_groups_array[] = '"'.$data['c_group_number'].' - '.$data['c_group_description'].'"';
            }
        }
        //$json_schema_output = str_replace('["void"]', '['.implode(',', $accounting_groups_array).']', $json_schema_output);
        
        
        //$this->container['settings']['glued']['hostname']
        //$this->container->settings->glued->hostname
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('accounting.api.new').'",
      dataType: "text",
      type: "POST",
      data: "billdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        
        ReactDOM.render((<div><h1>Form data</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre><h2>Final data</h2><pre>{data}</pre></div>), 
                 document.getElementById("main"));
        
      },
      error: function(xhr, status, err) {
        alert(status + err + data);
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        return $this->container->view->render($response, 'accounting/addcost.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output,
            'json_formdata_render_custom_array' => '1'
        ));
    }
    
    // show form for edit existing cost
    public function editCostForm($request, $response, $args)
    {
        
        $this->container->db->where("c_uid", $args['id']);
        $data = $this->container->db->getOne('t_accounting_received');
        
        $form_output = '';
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/bill_form_ui.json');
        
        // schema editacniho formulare je stejne jako schema pro novy zaznam
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/cost_columns.json');
        
        // zakladni data pro editaci
        $json_formdata_output = $data['c_data'];
        
        // upravime data, do kterych pridame levy a pravy sloupec
        // musime to udelat jako objekt
        $formdata = json_decode($json_formdata_output);
        
        // pridame si tam ty sloupce
        $formdata->data->left_column = new \stdClass();
        $formdata->data->right_column = new \stdClass();
        
        // definujem si klice
        $left_keys = array("dt_taxable",
              "dt_due",
              "inv_nr",
              "note",
              "ext_id",
              "supplier"  );
        $right_keys = array("acc_total_novat",
              "acc_total_vat",
              "acc_curr",
              "inv_total_novat",
              "inv_total_vat",
              "xr",
              "inv_curr",
              "managerial_acc",
              "financial_acc");
        
        // presunem to
        foreach ($left_keys as $key) {
            if (isset($formdata->data->$key)) {
                $formdata->data->left_column->$key = $formdata->data->$key;
                unset($formdata->data->$key);
            }
        }
        foreach ($right_keys as $key) {
            if (isset($formdata->data->$key)) {
                $formdata->data->right_column->$key = $formdata->data->$key;
                unset($formdata->data->$key);
            }
        }
        
        // prevedem to zpet na json retezec
        $json_formdata_output_upravena = json_encode($formdata);
        
        // pridame do schematu vlastni enum optiony, ted se nepouziva
        $accounting_groups_array = '';
        $this->container->db->where("c_definition_id", 1);
        $this->container->db->orderBy("c_group_number","asc");
        $groups = $this->container->db->get('t_accounting_account_groups');
        if (count($groups) > 0) {
            foreach ($groups as $data) {
                $accounting_groups_array[] = '"'.$data['c_group_number'].' - '.$data['c_group_description'].'"';
            }
        }
        //$json_schema_output = str_replace('["void"]', '['.implode(',', $accounting_groups_array).']', $json_schema_output);
        
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('accounting.api.edit').$args['id'].'",
      dataType: "text",
      type: "PUT",
      data: "billdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        
        ReactDOM.render((<div><h1>Form data</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre><h2>Final data</h2><pre>{data}</pre></div>), 
                 document.getElementById("main"));
        
      },
      error: function(xhr, status, err) {
        alert(status + err + data);
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        // nahrajem si soubory
        /*
        $vystup_souboru = '';
        $sloupce = array("lin.c_uid", "lin.c_owner", "lin.c_filename", "obj.sha512", "obj.doc->>'$.data.size' as size", "obj.doc->>'$.data.mime' as mime", "obj.doc->>'$.data.ts_created' as ts_created");
        $this->container->db->join("t_stor_objects obj", "obj.sha512=lin.c_sha512", "LEFT");
        $this->container->db->where("c_inherit_table", "t_accounting_received");
        $this->container->db->where("c_inherit_object", $args['id']);
        $this->container->db->orderBy("lin.c_filename","asc");
        $files = $this->container->db->get('t_stor_links lin', null, $sloupce);
        if (count($files) > 0) {
            foreach ($files as $filedata) {
                $adresa = $this->container->router->pathFor('stor.serve.file', ['id' => $filedata['c_uid'], 'filename' => $filedata['c_filename']]);
                $vystup_souboru .= '
                <div>
                    <a href="'.$adresa.'" class="">
                        <br />
                        '.$filedata['c_filename'].'
                        <a class="remove" href="#" data-toggle="modal" data-target="#confirm-modal" onclick="$(\'#delete_file_uid\').val('.$filedata['c_uid'].');">
                            <i class="fa fa-trash-o "></i>
                        </a>
                    </a>
                </div>
                ';
            }
        }
        else {
            $vystup_souboru .= '<div>no files uploaded</div>';
        }
        */
        
        // js funkce ktera zajisti delete souboru a zavola pak nas callback
        $additional_javascript = '
            <script>
            // funkce, kterou smazeme soubor ajaxem a obnovime vypis souboru podle zadaneho filtru
            function delete_stor_file_ajax() {
                var link_id = $("#delete_file_uid").val();
                $.ajax({
                  url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('stor.ajax.delete').'",
                  type: "POST",
                  dataType: "text",
                  data: { link_id: link_id },
                  success: function(data) {
                    // vypise znova soubory
                    list_stor_files_basic("uploaded_files_output", "costs", "'.$args['id'].'");
                  }
                });
            }
            
            // obecna funkce, kterou vypiseme do ciloveho dom elementu soubory patrici zadanemu objektu
            // TODO, bude v globalnim js, aby ji mohly pouzit vsecky moduly
            function list_stor_files_basic(target_id, module, object_id) {
                var dirname = module + "/" + object_id;
                $.ajax({
                  url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('stor.ajax.list.basic').'",
                  dataType: "text",
                  type: "GET",
                  data: "dirname=" + dirname,
                  success: function(data) {
                    $("#" + target_id).html(data);
                    //alert("files listed");
                  },
                  error: function(xhr, status, err) {
                    alert("ERROR: xhr status: " + xhr.status + ", status: " + status + ", err: " + err);
                  }
                });
            }
            
            // uvodni naplneni souboru
            list_stor_files_basic("uploaded_files_output", "costs", "'.$args['id'].'");
            
            </script>
        ';
        
        //             'vystup_souboru' => $vystup_souboru,
        return $this->container->view->render($response, 'accounting/editcost.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output_upravena,
            'json_onsubmit_output' => $json_onsubmit_output,
            'cost_id' => $args['id'],

            'json_formdata_render_custom_array' => '1',
            'stor_delete_modal' => '1',
            'additional_javascript' => $additional_javascript
        ));
    }
    
    // manage groups gui, list all definitions
    public function getDefinitions($request, $response)
    {
        $groups_output = '';
        $sloupce = array("c_definition_id", "COUNT(*) as pocet");
        $this->container->db->groupBy("c_definition_id");
        $this->container->db->orderBy("c_definition_id","asc");
        $groups = $this->container->db->get('t_accounting_account_groups', null, $sloupce);
        if (count($groups) > 0) {
            foreach ($groups as $data) {
                // musime si nacist aspon jeden full radek, kvuli c_definition_name
                $this->container->db->where("c_definition_id", $data['c_definition_id']);
                $data1 = $this->container->db->getOne('t_accounting_account_groups');
                
                $groups_output .= '
                    <tr>
                        <th scope="row"></th>
                        <td>'.$data['c_definition_id'].'</td>
                        <td>'.$data1['c_definition_name'].'</td>
                        <td>'.$data['pocet'].'</td>
                        <td>
                            <a href="'.$this->container->router->pathFor('accounting.list.definition', ['id' => $data['c_definition_id']]).'">list</a>
                        </td>
                    </tr>
                ';
            }
        }
        else {
            $groups_output .= '
                <tr>
                    <th scope="row"></th>
                    <td colspan="4">no ids at the moment</td>
                </tr>
            ';
        }
        
        
        $additional_javascript = '
    <script>

    </script>
        ';
        
        return $this->container->view->render($response, 'accounting/groups.twig', array(
            'groups_output' => $groups_output,
            'additional_javascript' => $additional_javascript,
            'ui_menu_active' => 'accounting.costs'
        ));
    }
    
    // list groups in one definition
    public function listDefinition($request, $response, $args)
    {
        $groups_output = '';
        $this->container->db->where("c_definition_id", $args['id']);
        $this->container->db->orderBy("c_group_number","asc");
        $groups = $this->container->db->get('t_accounting_account_groups');
        if (count($groups) > 0) {
            foreach ($groups as $data) {
                $groups_output .= '
                    <tr>
                        <th scope="row"></th>
                        <td>'.$data['c_definition_id'].'</td>
                        <td>'.$data['c_definition_name'].'</td>
                        <td>'.$data['c_group_number'].'</td>
                        <td>'.$data['c_group_description'].'</td>
                        <td>
                            <a href="'.$this->container->router->pathFor('accounting.list.definition', ['id' => $data['c_definition_id']]).'">edit</a>
                        </td>
                    </tr>
                ';
            }
        }
        else {
            $groups_output .= '
                <tr>
                    <th scope="row"></th>
                    <td colspan="4">no ids at the moment</td>
                </tr>
            ';
        }
        
        
        $additional_javascript = '
    <script>

    </script>
        ';
        
        return $this->container->view->render($response, 'accounting/definition.twig', array(
            'definition' => $args['id'],
            'groups_output' => $groups_output,
            'additional_javascript' => $additional_javascript,
            'ui_menu_active' => 'accounting.costs'
        ));
    }
    
    
    
}
