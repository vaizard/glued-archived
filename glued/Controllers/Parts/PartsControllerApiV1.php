<?php

namespace Glued\Controllers\Parts;
use Glued\Controllers\Controller;

class PartsControllerApiV1 extends Controller
{
    
    // api for add new cost (parametr args neni potreba, post promenna bude v request)
    public function insertApi($request, $response)
    {
        
        $senddata = $request->getParam('stockdata');
        
        $data = Array ("c_data" => $senddata );
        
        $insert = $this->container->db->insert('t_parts_items', $data);
        
        if ($insert) {
            $editacni_adresa = 'https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('parts.editform', ['id' => $insert]);
        }
        else {
            $editacni_adresa = 'https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('parts.addform');
        }
        
        // vratime adresu na editaci prave vlozeneho
       $response->getBody()->write($editacni_adresa);
       return $response;
        
    }
    
    // api for edit (parametr args ma, jdeme pres put, takze id bude v nem)
    public function editApi($request, $response, $args)
    {
        
        $senddata = $request->getParam('stockdata');
        
        $this->container->db->where('c_uid', $args['id']);
        $update = $this->container->db->update('t_parts_items', Array ( 'c_data' => $senddata ));
        
        // vratime prosty text
        $response->getBody()->write('ok');
        
       return $response;
        
    }
    
    /*
    // api for delete (parametr args ma, jedeme pres delete, takze id bude v nem)
    public function deleteApi($request, $response, $args)
    {
        
        $this->container->db->where('c_uid', $args['id']);
        $delete = $this->container->db->delete('t_parts_items');
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    */
}
