<?php

namespace Glued\Controllers\Vectors;
use Glued\Controllers\Controller;

class VectorsController extends Controller
{
    
    // shows vectors
    public function vectorsMain($request, $response)
    {
        $vectors_output = '';
        
        $sloupce = array("c_uid", "c_data");
        $vectors = $this->container->db->get('t_vectors', null, $sloupce);
        if ($this->container->db->count > 0) {
            foreach ($vectors as $vector) {
                $vector_json = json_decode($vector['c_data'], true);
                
                $vectors_output .= '<tr>
                    <td>'.$vector['c_uid'].'</td>
                    <td><a href="'.$this->container->router->pathFor('vectors.vector', ['id' => $vector['c_uid']]).'">'.$vector_json['data']['summary'][0]['data'].'</a></td>
                    <td></td>
                </tr>';
            }
        }
        
        
        return $this->container->view->render($response, 'vectors/gui.twig',
            array(
                'vectors_output' => $vectors_output
            )
        );
    }
    
    
    // show info about one vector
    public function Vector($request, $response, $args)
    {
        $event_id = $args['id'];
        $this->container->db->where('c_uid', $event_id);
        $event = $this->container->db->getOne('t_vectors');
        
        $event['c_data'] = str_replace('\n', '<br>', $event['c_data']);
        
        $event_json = json_decode($event['c_data'], true);  // vytvori objekt, ktery pak muzeme ve view zobrazovat s konvenci event_json.name atd.
        
        return $this->container->view->render($response, 'vectors/vector.twig',
            array(
                'event' => $event,
                'event_json' => $event_json,
                'source_json' => print_r($event_json, true)
            )
        );
    }
    
    
    // show form for add new vector
    public function addVectorForm($request, $response)
    {
        $form_output = '';
        
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/form_ui.json');
        
        // schema celeho formulare pro novy zaznam
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/vector.json');
        
        // zakladni data, nic (mame tam nejake defaulty ve schematu, ktere se snad nastavi)
        $json_formdata_output = '
{
}
        ';
        
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('vectors.api.new').'",
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
        
        return $this->container->view->render($response, 'vectors/addvector.twig', array(
            'form_output' => $form_output,
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output,
            'json_formdata_render_custom_array' => '1'
        ));
    }
    
}
