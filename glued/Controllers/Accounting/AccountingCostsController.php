<?php
namespace Glued\Controllers\Accounting;

use Glued\Controllers\Controller;

class AccountingCostsController extends Controller
{
    
    // shows basic page with all costs
    public function getCosts($request, $response)
    {
        $costs_output = '';
        $this->container->db->orderBy("c_uid","asc");
        $bills = $this->container->db->get('accounting_accepted');
        if (count($bills) > 0) {
            foreach ($bills as $data) {
                $billdata = json_decode($data['c_data'], true);
                $costs_output .= '<div id="cost_row_'.$data['c_uid'].'">ID: '.$data['c_uid'].', bill number: '.$billdata['bill-nr'].' <a href="'.$this->container->router->pathFor('accounting.editcostform').$data['c_uid'].'">edit</a> | <span style="cursor: pointer; color: red;" onclick="delete_cost('.$data['c_uid'].');">delete</span></div>';
            }
        }
        else {
            $costs_output = 'zatim zadne nejsou vlozeny';
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
        $modal_acl_table = $this->container->acl->modal_output_rights('accounting_accepted', 'table');
        $modal_acl_global = $this->container->acl->modal_output_rights('accounting_accepted', 'global');
        
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
        $form_output = 'zde je formular';
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/bill_form_ui.json');
        
        // schema celeho formulare
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/new_bill_form.json');
        
        // zakladni data, jedna prazdna polozka arraye "prirazeni", aby se tam vykreslil prvni prazdny prvek formulare
        $json_formdata_output = '
{
  "prirazeni": [
    {
      "zakazka-nr": "",
      "sum": "",
      "poznamka": ""
    }
  ]
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
        
        ReactDOM.render((<div><h1>Thank you</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
        
      },
      error: function(xhr, status, err) {
        
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
            'json_onsubmit_output' => $json_onsubmit_output
        ));
    }
    
    // show form for edit existing cost
    public function editCostForm($request, $response, $args)
    {
        
        $this->container->db->where("c_uid", $args['id']);
        $data = $this->container->db->getOne('accounting_accepted');
        
        
        $form_output = 'zde je formular';
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/bill_form_ui.json');
        
        // schema celeho editacniho formulare. je to prakticky shodne schema jako formular pro novy bill, krome title
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/edit_bill_form.json');
        
        // zakladni data pro editaci
        $json_formdata_output = $data['c_data'];
        
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('accounting.api.edit').$args['id'].'",
      dataType: "text",
      type: "PUT",
      data: "billdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        
        ReactDOM.render((<div><h1>Record was updated succesfully</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
        
      },
      error: function(xhr, status, err) {
        
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        
        return $this->container->view->render($response, 'accounting/editcost.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output,
            'cost_id' => $args['id']
        ));
    }
    
}
