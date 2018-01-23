<?php
namespace Glued\Controllers\Permissions;

use Glued\Controllers\Controller;   // kvuli extends

class PermissionsControllerApiV1 extends Controller
{
    // api for delete (parametr args ma, jedeme pres delete, takze id bude v nem)
    public function deletePrivilegeApi($request, $response, $args)
    {
        
        $this->container->db->where('c_id', $args['id']);
        $delete = $this->container->db->delete('t_privileges');
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
}
