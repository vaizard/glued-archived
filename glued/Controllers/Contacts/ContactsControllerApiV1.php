<?php
namespace Glued\Controllers\Contacts;

use Glued\Controllers\Controller;

class ContactsControllerApiV1 extends Controller
{
    
    // api for add new cost (parametr args neni potreba, post promenna bude v request)
    public function insertContactApi($request, $response)
    {
        
        $senddata = $request->getParam('billdata');
        $user_id = $_SESSION['user_id'];
        
        // upravime send data, co je v left_column a v right_column dame o uroven nahoru
        // musime to upravovat jako objekt
        $send_data_object = json_decode($senddata);
        $left_data = $send_data_object->data->left_column;
        $right_data = $send_data_object->data->right_column;
        unset($send_data_object->data->left_column);
        unset($send_data_object->data->right_column);
        foreach($left_data as $k => $v) { $send_data_object->data->$k = $v; }
        foreach($right_data as $k => $v) { $send_data_object->data->$k = $v; }
        $senddata_upravena = json_encode($send_data_object);
        
        // vlozime to jak to prislo z formu
        // 500 = 111 110 100
        $data = Array ("c_owner" => $user_id, "c_group" => 2, "c_unixperms" => 500,
                       "c_data" => $senddata_upravena
        );
        $insert = $this->container->db->insert('t_contacts', $data);
        if ($insert) {
            // prepiseme creator, dt_created, _id
            $this->container->db->rawQuery("UPDATE t_contacts SET c_data = JSON_SET(c_data, '$.data._id', ?, '$.data.creator', ?, '$.data.dt_created', ?) WHERE c_uid = ? ", Array (strval($insert), strval($user_id), date("Y-m-d H:i:s"), $insert));
            
            if ($this->container->db->getLastErrno() === 0) {
                $response->getBody()->write($senddata_upravena);
            }
            else {
                $response->getBody()->write('update failed: ' . $this->container->db->getLastError());
            }
        }
        else {
            $response->getBody()->write('error');
        }
        
        // vratime prosty text
        return $response;
        
    }
    
    // api for edit (parametr args ma, jdeme pres put, takze id bude v nem)
    public function editContactApi($request, $response, $args)
    {
        
        $senddata = $request->getParam('billdata');
        
        // upravime send data, co je v left_column a v right_column dame o uroven nahoru
        // musime to upravovat jako objekt
        $send_data_object = json_decode($senddata);
        $left_data = $send_data_object->data->left_column;
        $right_data = $send_data_object->data->right_column;
        unset($send_data_object->data->left_column);
        unset($send_data_object->data->right_column);
        foreach($left_data as $k => $v) { $send_data_object->data->$k = $v; }
        foreach($right_data as $k => $v) { $send_data_object->data->$k = $v; }
        $senddata_upravena = json_encode($send_data_object);
        
        $this->container->db->where('c_uid', $args['id']);
        $update = $this->container->db->update('t_contacts', Array ( 'c_data' => $senddata_upravena ));
        
        // vratime prosty text
       $response->getBody()->write($senddata_upravena);
       return $response;
        
    }
    
    // api for delete (parametr args ma, jedeme pres delete, takze id bude v nem)
    public function deleteContactApi($request, $response, $args)
    {
        
        $this->container->db->where('c_uid', $args['id']);
        $delete = $this->container->db->delete('t_contacts');
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
}
