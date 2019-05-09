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
            'additional_javascript' => $additional_javascript));
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
        $formdata = json_decode($json_formdata_output, true);
        $additional_data['left_column'] = array();
        $additional_data['right_column'] = array();
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
              "financial_acc",
              "files");
        foreach ($left_keys as $key) {
            if (isset($formdata['data'][$key])) {
                $additional_data['left_column'][$key] = $formdata['data'][$key];
                unset($formdata['data'][$key]);
            }
        }
        foreach ($right_keys as $key) {
            if (isset($formdata['data'][$key])) {
                $additional_data['right_column'][$key] = $formdata['data'][$key];
                unset($formdata['data'][$key]);
            }
        }
        $new_data['data'] = array_merge($formdata['data'], $additional_data);
        $json_formdata_output_upravena = json_encode($new_data);
        
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
        
        
        return $this->container->view->render($response, 'accounting/editcost.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output_upravena,
            'json_onsubmit_output' => $json_onsubmit_output,
            'cost_id' => $args['id'],
            'json_formdata_render_custom_array' => '1'
        ));
    }
    
}
