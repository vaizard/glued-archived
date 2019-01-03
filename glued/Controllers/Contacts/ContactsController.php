<?php
namespace Glued\Controllers\Contacts;

use Glued\Controllers\Controller;

class ContactsController extends Controller
{
    
    // shows basic page with all costs
    public function contactsMain($request, $response)
    {
        $costs_output = '';
        $sloupce = array("c_uid", "c_data->>'$.data.doc_id' as doc_id", "c_data->>'$.data.fn' as name");
        $this->container->db->orderBy("c_uid","asc");
        $bills = $this->container->db->get('t_contacts', null, $sloupce);
        if (count($bills) > 0) {
            foreach ($bills as $data) {
                $costs_output .= '
                    <tr id="cost_row_'.$data['c_uid'].'">
                        <th scope="row">'.$data['c_uid'].'</th>
                        <td>'.$data['name'].'</td>
                        <td>...</td>
                        <td><a href="'.$this->container->router->pathFor('contacts.editcontactform').$data['c_uid'].'">edit</a></td>
                        <td><span style="cursor: pointer; color: red;" onclick="delete_cost('.$data['c_uid'].');">delete</span></td>
                    </tr>
                ';
            }
        }
        else {
            $costs_output .= '
                <tr>
                    <th scope="row"></th>
                    <td colspan="4">no contacts at the moment</td>
                </tr>
            ';
        }
        
        $additional_javascript = '
    <script>
    function delete_cost(cost_id) {
        if (confirm("do you really want to delete this contact?")) {
            $.ajax({
              url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('contacts.api.delete').'" + cost_id,
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
        
        // pripravim obsah modalu pro tabulku t_contacts
        $modal_acl_table = $this->container->permissions->modal_output_rights('t_contacts', 'table');
        $modal_acl_global = $this->container->permissions->modal_output_rights('t_contacts', 'global');
        
        // nacteme si mozne akce, TODO spis dat do ajaxu primo do formu
        $action_options = '';
        $akce = $this->container->db->get('t_action');
        if ($this->container->db->count > 0) {
            foreach ($akce as $akce1) {
                $action_options .= '<option value="'.$akce1['c_title'].'">'.$akce1['c_title'].' ('.($akce1['c_apply_object'] == 1?'object':'table').')</option>';
            }
        }
        
        return $this->container->view->render($response, 'contacts/main.twig', array(
            'related_table' => 't_contacts',
            'return_modal_form_uri' => $this->container->router->pathFor('contacts.main'),
            'action_options' => $action_options,
            'modal_acl_table' => $modal_acl_table,
            'modal_acl_global' => $modal_acl_global,
            'costs_output' => $costs_output,
            'additional_javascript' => $additional_javascript));
    }
    
    // show form for add new cost
    public function addContactForm($request, $response)
    {
        $form_output = '';
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/contact_form_ui.json');
        
        // schema celeho formulare
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/new_contact_form.json');
        
        // zakladni data, jedna polozka procentualni dane
        $json_formdata_output = '
{
    "data":{
    }
}
        ';
        //$this->container['settings']['glued']['hostname']
        //$this->container->settings->glued->hostname
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('contacts.api.new').'",
      dataType: "text",
      type: "POST",
      data: "billdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        
        ReactDOM.render((<div><h1>Thank you</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
        
      },
      error: function(xhr, status, err) {
        alert(status + err + data);
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        return $this->container->view->render($response, 'contacts/addcontact.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output,
            'json_formdata_render_custom_array' => '1'
        ));
    }
    
    // show form for edit existing cost
    public function editContactForm($request, $response, $args)
    {
        
        $this->container->db->where("c_uid", $args['id']);
        $data = $this->container->db->getOne('t_contacts');
        
        $form_output = '';
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/contact_form_ui.json');
        
        // schema celeho editacniho formulare. je to prakticky shodne schema jako formular pro novy bill, krome title
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/edit_contact_form.json');
        
        // zakladni data pro editaci
        $json_formdata_output = $data['c_data'];
        
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('contacts.api.edit').$args['id'].'",
      dataType: "text",
      type: "PUT",
      data: "billdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        
        ReactDOM.render((<div><h1>Record was updated succesfully</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
        
      },
      error: function(xhr, status, err) {
        alert(status + err + data);
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        
        return $this->container->view->render($response, 'contacts/editcontact.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output,
            'cost_id' => $args['id'],
            'json_formdata_render_custom_array' => '1'
        ));
    }
    
}
