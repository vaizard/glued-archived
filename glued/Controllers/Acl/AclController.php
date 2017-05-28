<?php
namespace Glued\Controllers\Acl;

use Glued\Controllers\Controller; // needed because Auth is in a directory below

class AclController extends Controller
{

    // shows basic page with acl options
    public function getAclCrossroad($request, $response)
    {
        $vystup_users = '';
        $this->container->db->orderBy("c_uid","asc");
        $uzivatele = $this->container->db->get('t_users');
        foreach ($uzivatele as $data) {
            $vystup_users .= '<div>ID: '.$data['c_uid'].', name: '.$data['c_screenname'].' <a href="usergroups/'.$data['c_uid'].'">group membership</a> | <a href="userunix/'.$data['c_uid'].'">unix acl</a> | <a href="userprivileges/'.$data['c_uid'].'">user privileges</a></div>';
        }
        
        $groups_array = $this->container->acl->show_groups();
        $vystup_groups = '';
        foreach ($groups_array as $group_name => $group_id) {
            $vystup_groups .= '<div>ID: '.$group_id.', name: '.$group_name.' <a href="groupprivileges/'.$group_id.'">group privileges</a></div>';
        }
        
        $vystup_actions = '';
        $akce = $this->container->db->get('t_action');
        if (count($akce) > 0) {
            foreach ($akce as $akce1) {
                $vystup_actions .= '<div><strong>'.$akce1['c_title'].'</strong> (apply on '.($akce1['c_apply_object'] == 1?'objects':'tables').')</div>';
            }
        }
        else {
            $vystup_actions .= '<div>no actions are defined</div>';
        }
        
        $vystup_hardcoded_permissions = '<div>Permissions: <br />'.print_r($this->container->acl->show_permissions(), true).'</div>';
        $vystup_hardcoded_groups = '<div>Groups: <br />'.print_r($groups_array, true).'</div>';
        
        return $this->container->view->render($response, 'acl/crossroads.twig', array('vystup_users' => $vystup_users, 'vystup_groups' => $vystup_groups, 'vystup_hardcoded_permissions' => $vystup_hardcoded_permissions, 'vystup_hardcoded_groups' => $vystup_hardcoded_groups, 'vystup_actions' => $vystup_actions));
    }
    
    // post for add action from crossroads form
    public function postAddAction($request, $response) {
        
        // nazev musi byt vyplnen
        if (!empty($request->getParam('title'))) {
            
            $title = $request->getParam('title');
            $apply_object = $request->getParam('apply_object');
            
            $data = Array ("c_title" => $title,
                           "c_apply_object" => $apply_object
            );
            
            $insert = $this->container->db->insert('t_action', $data);
            
            if (!$insert) {
                $this->container->logger->warn("Action was not added. DB error.");
                return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
            }
            else {
                $this->container->flash->addMessage('info', 'New action was added');
                return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
            }
        }
        else {
            $this->container->flash->addMessage('info', 'you left title empty !');
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
    }
    
    // shows edit page for groups membership of one user
    public function getUserGroups($request, $response, $args)
    {
        
        $this->container->db->where("c_uid", $args['id']);
        $user = $this->container->db->getOne('t_users');
        
        $membership = array();
        if ($user['c_group_mememberships'] & 1) { $membership['root'] = true; }
        else { $membership['root'] = false; }
        if ($user['c_group_mememberships'] & 2) { $membership['officer'] = true; }
        else { $membership['officer'] = false; }
        if ($user['c_group_mememberships'] & 4) { $membership['user'] = true; }
        else { $membership['user'] = false; }
        if ($user['c_group_mememberships'] & 8) { $membership['wheel'] = true; }
        else { $membership['wheel'] = false; }
        
        return $this->container->view->render($response, 'acl/usergroups.twig', array('user' => $user, 'membership' => $membership));
    }

    // sets new membership for user
    public function postUserGroups($request, $response)
    {
        $user_id = $request->getParam('user_id');
        
        if ($user_id) {
            
            // vypocitame novy group membership
            $group_sum = 0;
            if (!empty($request->getParam('chb_root'))) { $group_sum += 1; }
            if (!empty($request->getParam('chb_officer'))) { $group_sum += 2; }
            if (!empty($request->getParam('chb_user'))) { $group_sum += 4; }
            if (!empty($request->getParam('chb_wheel'))) { $group_sum += 8; }
            
            // change membership
            $this->container->db->where('c_uid', $user_id);
            $update = $this->container->db->update('t_users', Array ( 'c_group_mememberships' => $group_sum ));
            
            if (!$update) {
                $this->container->logger->warn("User group membership change failed. DB error.");
                return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
            }
            else {
                $this->container->flash->addMessage('info', 'User group membership was changed');
                return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
            }
            
        }
        else {
            $this->container->flash->addMessage('info', 'neprosli jsme');
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
            
        }
    }
    
    
    // shows edit page for unix acl of one user
    public function getUserUnix($request, $response, $args)
    {
        
        $this->container->db->where("c_uid", $args['id']);
        $user = $this->container->db->getOne('t_users');
        
        $permissions = array();
        $permissions_array = $this->container->acl->show_permissions();
        
        foreach ($permissions_array as $i => $k) {
            if ($user['c_unixperms'] & $k) { $permissions[$i] = true; }
            else { $permissions[$i] = false; }
        }
        
        return $this->container->view->render($response, 'acl/userunix.twig', array('user' => $user, 'permissions' => $permissions));
    }

    // sets new unixperms for user
    public function postUserUnix($request, $response)
    {
        $user_id = $request->getParam('user_id');
        $permissions_array = $this->container->acl->show_permissions();
        
        if ($user_id) {
            
            // vypocitame novy group unixperms
            $sum = 0;
            foreach ($permissions_array as $i => $k) {
                if (!empty($request->getParam($i))) { $sum += $k; }
            }
            
            // change unixperms
            $this->container->db->where('c_uid', $user_id);
            $update = $this->container->db->update('t_users', Array ( 'c_unixperms' => $sum ));
            
            if (!$update) {
                $this->container->logger->warn("User unixperms change failed. DB error.");
                return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
            }
            else {
                $this->container->flash->addMessage('info', 'User unixperms was changed');
                return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
            }
            
        }
        else {
            $this->container->flash->addMessage('info', 'neprosli jsme');
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
            
        }
    }
    
    
    // shows edit page for module acl of one user - table t_privileges
    public function getUserPrivileges($request, $response, $args)
    {
        $this->container->db->where("c_uid", $args['id']);
        $user = $this->container->db->getOne('t_users');
        
        $vystup_privileg = '';
        $this->container->db->where("c_role", 'user');
        $this->container->db->where("c_who", $args['id']);
        $privileges = $this->container->db->get('t_privileges');
        if ($this->container->db->count > 0) {
            foreach ($privileges as $privilege) {
                $vystup_privileg .= '<div>'.$privilege['c_action'].', '.$privilege['c_type'].', '.$privilege['c_related_table'].' edit, delete</div>';
            }
        }
        else {
            $vystup_privileg .= 'no privileges at the moment';
        }
        
        return $this->container->view->render($response, 'acl/userprivileges.twig', array('user' => $user, 'privileges_output' => $vystup_privileg));
    }
    
    // shows edit page for module acl of one group - table t_privileges
    public function getGroupPrivileges($request, $response, $args)
    {
        $groups_array = $this->container->acl->show_groups();
        $group_id = (int) $args['id'];
        
        if (in_array($group_id, $groups_array)) {
            $group_name = array_search($group_id, $groups_array);
            $vystup_privileg = '';
            $this->container->db->where("c_role", 'group');
            $this->container->db->where("c_who", $group_id);
            $privileges = $this->container->db->get('t_privileges');
            if ($this->container->db->count > 0) {
                foreach ($privileges as $privilege) {
                    $vystup_privileg .= '<div>'.$privilege['c_action'].', '.$privilege['c_type'].', '.$privilege['c_related_table'].' edit, delete</div>';
                }
            }
            else {
                $vystup_privileg .= 'no privileges at the moment';
            }
            
            return $this->container->view->render($response, 'acl/groupprivileges.twig', array('group_name' => $group_name, 'group_id' => $group_id, 'privileges_output' => $vystup_privileg));
        }
        else {
            $this->container->flash->addMessage('info', 'bad group id');
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
    }
    
    // shows edit page who has the privileges for one table - table t_privileges
    public function getTablePrivileges($request, $response, $args)
    {
        $allowed_tables_array = array('platby_mzdy', 'timepixels');
        $table_name = $args['tablename'];
        
        if (in_array($table_name, $allowed_tables_array)) {
            $vystup_privileg = '';
            
            $this->container->db->where("c_type", 'table');
            $this->container->db->where("c_related_table", $table_name);
            $privileges = $this->container->db->get('t_privileges');
            if ($this->container->db->count > 0) {
                foreach ($privileges as $privilege) {
                    $vystup_privileg .= '<div>'.$privilege['c_role'].' (id: '.$privilege['c_who'].'), action: '.$privilege['c_action'].($privilege['c_neg'] == 1?', negative':'').'</div>';
                }
            }
            else {
                $vystup_privileg .= 'no privileges at the moment';
            }
            
            return $this->container->view->render($response, 'acl/tableprivileges.twig', array('table_name' => $table_name, 'privileges_output' => $vystup_privileg));
        }
        else {
            $this->container->flash->addMessage('info', 'bad table name');
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
    }
    
    // insert new privilege (from various pages)
    public function postNewPrivilege($request, $response) {
        
        $role = $request->getParam('role');
        $who = $request->getParam('who');
        $action = $request->getParam('action');
        $type = $request->getParam('type');
        $related_table = $request->getParam('related_table');
        if ($type == 'object') {
            $related_uid = $request->getParam('related_uid');
        }
        else {
            $related_uid = 0;
        }
        if (!empty($request->getParam('neg'))) { $neg = 1; }
        else { $neg = 0; }
        
        $data = Array ("c_role" => $role, "c_who" => $who, "c_action" => $action, "c_type" => $type, "c_related_table" => $related_table, "c_related_uid" => $related_uid, "c_neg" => $neg );
        
        $insert = $this->container->db->insert('t_privileges', $data);
        
        if (!$insert) {
            $this->container->logger->warn("Action was not added. DB error.");
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
        else {
            $this->container->flash->addMessage('info', 'New privilege was added');
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
    }
    
    
}
