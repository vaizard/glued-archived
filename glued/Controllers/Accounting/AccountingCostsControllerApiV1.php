<?php
namespace Glued\Controllers\Accounting;

use Glued\Controllers\Controller;

class AccountingCostsControllerApiV1 extends Controller
{
    
    // api for add new cost (parametr args neni potreba, post promenna bude v request)
    public function insertCostApi($request, $response)
    {
        
        $senddata = $request->getParam('billdata');
        
        $data = Array ("c_owner" => 1, "c_group" => 1, "c_unixperms" => 500,
                       "c_data" => $senddata
        );
        
        $insert = $this->container->db->insert('accounting_accepted', $data);
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
    // api for edit (parametr args ma, jdeme pres put, takze id bude v nem)
    public function editCostApi($request, $response, $args)
    {
        
        $senddata = $request->getParam('billdata');
        
        $this->container->db->where('c_uid', $args['id']);
        $update = $this->container->db->update('accounting_accepted', Array ( 'c_data' => $senddata ));
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
    // api for delete (parametr args ma, jedeme pres delete, takze id bude v nem)
    public function deleteCostApi($request, $response, $args)
    {
        
        $this->container->db->where('c_uid', $args['id']);
        $delete = $this->container->db->delete('accounting_accepted');
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
}
