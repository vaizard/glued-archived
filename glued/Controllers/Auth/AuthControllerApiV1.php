<?php
namespace Glued\Controllers\Auth;

use Glued\Controllers\Controller;

class AuthControllerApiV1 extends Controller
{
    
    // api for edit (parametr args ma, jdeme pres put, takze id bude v nem)
    public function editProfileApi($request, $response, $args)
    {
        $user_id = $this->container->auth_user->user_id;
        $senddata = $request->getParam('billdata');
        
        // odstranime z toho uzly cc a roles
        $senddata_objekt = json_decode($senddata);
        unset($senddata_objekt->data->cc);
        unset($senddata_objekt->data->roles);
        $senddata = json_encode($senddata_objekt);
        
        $this->container->db->where('c_uid', $user_id);
        $update = $this->container->db->update('t_users', Array ( 'profile_data' => $senddata ));
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
    
}
