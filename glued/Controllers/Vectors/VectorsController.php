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
    
    
}
