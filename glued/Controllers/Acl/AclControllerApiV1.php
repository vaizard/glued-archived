<?php
namespace Glued\Controllers\Acl;

use Glued\Controllers\Controller; // needed because Auth is in a directory below

class AclControllerApiV1 extends Controller
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
