<?php
namespace Glued\Controllers\Assets;

use Glued\Controllers\Controller;

class StockControllerApiV1 extends Controller
{
    
    // api for add new cost (parametr args neni potreba, post promenna bude v request)
    public function insertStockApi($request, $response)
    {
        
        $senddata = $request->getParam('stockdata');
        
        $data = Array ("c_data" => $senddata );
        
        $insert = $this->container->db->insert('t_assets_items', $data);
        
        if ($insert) {
            $editacni_adresa = 'https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('assets.editform', ['id' => $insert]);
        }
        else {
            $editacni_adresa = 'https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('assets.addform');
        }
        
        // vratime adresu na editaci prave vlozeneho
       $response->getBody()->write($editacni_adresa);
       return $response;
        
    }
    
    // api for edit (parametr args ma, jdeme pres put, takze id bude v nem)
    public function editStockApi($request, $response, $args)
    {
        
        $senddata = $request->getParam('stockdata');
        
        $this->container->db->where('c_uid', $args['id']);
        $update = $this->container->db->update('t_assets_items', Array ( 'c_data' => $senddata ));
        
        // vratime prosty text
        $response->getBody()->write('ok');
        
       return $response;
        
    }
    
    /*
    // api for delete (parametr args ma, jedeme pres delete, takze id bude v nem)
    public function deleteStockApi($request, $response, $args)
    {
        
        $this->container->db->where('c_uid', $args['id']);
        $delete = $this->container->db->delete('accounting_accepted');
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    */
}
