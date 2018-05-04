<?php
namespace Glued\Controllers\Permissions;

use Glued\Controllers\Controller;   // kvuli extends

class PermissionsControllerApiV1 extends Controller
{
    // api for permission delete (parametr args ma, jedeme pres delete, takze id bude v nem)
    public function deletePrivilegeApi($request, $response, $args)
    {
        
        $this->container->db->where('c_id', $args['id']);
        $delete = $this->container->db->delete('t_privileges');
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
    // api for implemented action delete (parametr args ma, jedeme pres delete, takze id bude v nem)
    public function deleteImpActionApi($request, $response, $args)
    {
        
        $this->container->db->where('c_id', $args['id']);
        $delete = $this->container->db->delete('t_implemented_action');
        
        // vratime prosty text
       $response->getBody()->write('ok');
       return $response;
        
    }
    
    // api for action delete (komplet smaze akci ze systemu)
    public function deleteActionApi($request, $response, $args)
    {
        $action_id = (int) $args['id'];
        // nacteme si nazev a typ
        $this->container->db->where("c_uid", $action_id);
        $action = $this->container->db->getOne('t_action');
        if ($this->container->db->count > 0) {
            
            $action_name = $action['c_title'];
            
            // z implemented action mazeme jen kdyz je to objektovy typ akce
            if ($action['c_apply_object'] == 1) {
                $this->container->db->where("c_action", $action_name);
                $this->container->db->delete('t_implemented_action');
            }
            
            // z permissions mazeme, ale s podminkou na typ permission
            $this->container->db->where("c_action", $action_name);
            if ($action['c_apply_object'] == 1) {   // object a global
                $this->container->db->where("(c_type = ? or c_type = ?)", Array('object', 'global'));
            }
            else {  // table
                $this->container->db->where("c_type", 'table');
            }
            $this->container->db->delete('t_privileges');
            
            // z action mazeme vzdy
            $this->container->db->where("c_uid", $action_id);
            $delete = $this->container->db->delete('t_action');
        }
        
        // vratime prosty text
        $this->container->flash->addMessage('info', 'Action was deleted');
       $response->getBody()->write('ok');
       return $response;
    }
    
    // api for action name update
    public function changeActionApi($request, $response, $args)
    {
        $action_id = (int) $args['id'];
        $newtitle = $request->getParam('newtitle');
        
        // nacteme si nazev a typ
        $this->container->db->where("c_uid", $action_id);
        $action = $this->container->db->getOne('t_action');
        if ($this->container->db->count > 0) {
            
            $action_name = $action['c_title'];
            
            // zjistime jestli se to nekrizi s existujicim nazvem, TODO
            
            
            
            // v implemented action menime jen kdyz je to objektovy typ akce
            if ($action['c_apply_object'] == 1) {
                $data = Array ('c_action' => $newtitle);
                $this->container->db->where("c_action", $action_name);
                $this->container->db->update('t_implemented_action', $data);
            }
            
            // v permissions menime, ale s podminkou na typ permission
            $data = Array ('c_action' => $newtitle);
            $this->container->db->where("c_action", $action_name);
            if ($action['c_apply_object'] == 1) {   // object a global
                $this->container->db->where("(c_type = ? or c_type = ?)", Array('object', 'global'));
            }
            else {  // table
                $this->container->db->where("c_type", 'table');
            }
            $this->container->db->update('t_privileges', $data);
            
            // v action menime vzdy
            $data = Array ('c_title' => $newtitle);
            $this->container->db->where("c_uid", $action_id);
            $delete = $this->container->db->update('t_action', $data);
        }
        
        // vratime prosty text
        $this->container->flash->addMessage('info', 'Action was renamed');
        $response->getBody()->write('ok');
        return $response;
    }
}
