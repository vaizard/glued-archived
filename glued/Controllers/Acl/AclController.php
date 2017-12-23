<?php
namespace Glued\Controllers\Acl;

use Glued\Controllers\Controller; // needed because Auth is in a directory below

class AclController extends Controller
{

    // shows basic page with acl options
    public function getAclCrossroad($request, $response)
    {
        $this->container->db->orderBy("c_uid","asc");
        $uzivatele = $this->container->db->get('t_users');
        $vystup_users = '
<table class="table table-hover">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Group membership</th>
            <th>Unix acl</th>
            <th>User privileges</th>
        </tr>
    </thead>
    <tbody>
        ';
        foreach ($uzivatele as $data) {
            $vystup_users .= '
        <tr>
            <th scope="row">'.$data['c_uid'].'</th>
            <td>'.$data['c_screenname'].'</td>
            <td><a href="usergroups/'.$data['c_uid'].'">set</a></td>
            <td><a href="userunix/'.$data['c_uid'].'">set</a></td>
            <td><a href="userprivileges/'.$data['c_uid'].'">edit</a></td>
        </tr>';
        }
        $vystup_users .= '
    </tbody>
</table>
        ';
        
        $groups_array = $this->container->acl->show_groups();
        $vystup_groups = '
<table class="table table-hover">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>group privileges</th>
        </tr>
    </thead>
    <tbody>
        ';
        foreach ($groups_array as $group_name => $group_id) {
            $vystup_groups .= '
        <tr>
            <th scope="row">'.$group_id.'</th>
            <td>'.$group_name.'</td>
            <td><a href="groupprivileges/'.$group_id.'">edit</a></td>
        </tr>';
        }
        $vystup_groups .= '
    </tbody>
</table>
        ';
        
        $vystup_actions['object'] = '';
        $vystup_actions['table'] = '';
        $this->container->db->where("c_apply_object", 1);
        $akce = $this->container->db->get('t_action');
        if (count($akce) > 0) {
            foreach ($akce as $akce1) {
                $vystup_actions['object'] .= '<div><strong>'.$akce1['c_title'].'</strong></div>';
            }
        }
        else {
            $vystup_actions['object'] .= '<div>no object actions are defined</div>';
        }
        $this->container->db->where("c_apply_object", 0);
        $akce = $this->container->db->get('t_action');
        if (count($akce) > 0) {
            foreach ($akce as $akce1) {
                $vystup_actions['table'] .= '<div><strong>'.$akce1['c_title'].'</strong></div>';
            }
        }
        else {
            $vystup_actions['table'] .= '<div>no table actions are defined</div>';
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
                $vystup_privileg .= '<div id="privilege_row_'.$privilege['c_id'].'">'.($privilege['c_neg'] == 0?'can':'can not !').' <strong>'.$privilege['c_action'].'</strong> ('.$privilege['c_type'].' type) on table <strong>'.$privilege['c_related_table'].'</strong>
                <span style="cursor: pointer; color: red; margin-left: 10px;" class="pull-right" onclick="delete_privilege('.$privilege['c_id'].');">delete</span>
                <span class="pull-right">[edit]</span>
                </div>';
            }
        }
        else {
            $vystup_privileg .= 'no privileges at the moment';
        }
        
        $additional_javascript = '
    <script>
    function delete_privilege(item_id) {
        if (confirm("do you really want to delete this privilege?")) {
            $.ajax({
              url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('acl.api.privilege.delete').'" + item_id,
              dataType: "text",
              type: "DELETE",
              data: "voiddata=1",
              success: function(data) {
                $("#privilege_row_" + item_id).remove();
              },
              error: function(xhr, status, err) {
                alert("ERROR: xhr status: " + xhr.status + ", status: " + status + ", err: " + err);
              }
            });
        }
    }
    </script>
        ';
        
        // nacteme si mozne akce
        $action_options = '';
        $akce = $this->container->db->get('t_action');
        if ($this->container->db->count > 0) {
            foreach ($akce as $akce1) {
                $action_options .= '<option value="'.$akce1['c_title'].'">'.$akce1['c_title'].' ('.($akce1['c_apply_object'] == 1?'object':'table').')</option>';
            }
        }
        
        
        return $this->container->view->render($response, 'acl/userprivileges.twig', array('user' => $user, 'privileges_output' => $vystup_privileg, 'action_options' => $action_options, 'additional_javascript' => $additional_javascript));
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
                    $vystup_privileg .= '<div>'.($privilege['c_neg'] == 0?'can':'can not !').' <strong>'.$privilege['c_action'].'</strong> ('.$privilege['c_type'].' type) on table <strong>'.$privilege['c_related_table'].'</strong> <span class="pull-right">[edit, delete]</span></div>';
                }
            }
            else {
                $vystup_privileg .= 'no privileges at the moment';
            }
            
            // nacteme si mozne akce
            $action_options = '';
            $akce = $this->container->db->get('t_action');
            if ($this->container->db->count > 0) {
                foreach ($akce as $akce1) {
                    $action_options .= '<option value="'.$akce1['c_title'].'">'.$akce1['c_title'].' ('.($akce1['c_apply_object'] == 1?'object':'table').')</option>';
                }
            }
            
            return $this->container->view->render($response, 'acl/groupprivileges.twig', array('group_name' => $group_name, 'group_id' => $group_id, 'privileges_output' => $vystup_privileg, 'action_options' => $action_options));
        }
        else {
            $this->container->flash->addMessage('info', 'bad group id');
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
    }
    
    // shows edit page for role privileges of other roles as self or creator
    public function getRolePrivileges($request, $response)
    {
        $this->container->db->where("c_uid", $args['id']);
        $user = $this->container->db->getOne('t_users');
        
        $vystup_privileg = '';
        $this->container->db->where("c_role", 'self');
        $this->container->db->orWhere("c_role", 'creator'); // OR
        $privileges = $this->container->db->get('t_privileges');
        if ($this->container->db->count > 0) {
            foreach ($privileges as $privilege) {
                $vystup_privileg .= '<div>role <strong>'.$privilege['c_role'].'</strong> '.($privilege['c_neg'] == 0?'can':'can not !').' <strong>'.$privilege['c_action'].'</strong> ('.$privilege['c_type'].' type) on table <strong>'.$privilege['c_related_table'].'</strong> <span class="pull-right">[edit, delete]</span></div>';
            }
        }
        else {
            $vystup_privileg .= 'no privileges at the moment';
        }
        
        // nacteme si mozne akce
        $action_options = '';
        $akce = $this->container->db->get('t_action');
        if ($this->container->db->count > 0) {
            foreach ($akce as $akce1) {
                $action_options .= '<option value="'.$akce1['c_title'].'">'.$akce1['c_title'].' ('.($akce1['c_apply_object'] == 1?'object':'table').')</option>';
            }
        }
        
        
        return $this->container->view->render($response, 'acl/roleprivileges.twig', array('user' => $user, 'privileges_output' => $vystup_privileg, 'action_options' => $action_options));
    }
    
    // shows edit page who has table privileges for one table - table t_privileges
    public function getTableTablePrivileges($request, $response, $args)
    {
        $allowed_tables_array = array('platby_mzdy', 'timepixels');
        $table_name = $args['tablename'];
        $privileges_type = 'table';
        
        if (in_array($table_name, $allowed_tables_array)) {
            $vystup_privileg = '';
            
            $this->container->db->where("c_type", 'table');
            $this->container->db->where("c_related_table", $table_name);
            $privileges = $this->container->db->get('t_privileges');
            if ($this->container->db->count > 0) {
                foreach ($privileges as $privilege) {
                    $vystup_privileg .= '<div>role <strong>'.$privilege['c_role'].'</strong> '.($privilege['c_neg'] == 0?'can':'can not !').' <strong>'.$privilege['c_action'].'</strong> ('.$privilege['c_type'].' type) on table <strong>'.$privilege['c_related_table'].'</strong> <span class="pull-right">[edit, delete]</span></div>';
                }
            }
            else {
                $vystup_privileg .= 'no privileges at the moment';
            }
            
            return $this->container->view->render($response, 'acl/tableprivileges.twig', array('table_name' => $table_name, 'privileges_output' => $vystup_privileg, 'privileges_type' => $privileges_type));
        }
        else {
            $this->container->flash->addMessage('info', 'bad table name');
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
    }
    
    // shows edit page who has global privileges for one table - table t_privileges
    public function getGlobalTablePrivileges($request, $response, $args)
    {
        $allowed_tables_array = array('platby_mzdy', 'timepixels');
        $table_name = $args['tablename'];
        $privileges_type = 'global';
        
        if (in_array($table_name, $allowed_tables_array)) {
            $vystup_privileg = '';
            
            $this->container->db->where("c_type", 'global');
            $this->container->db->where("c_related_table", $table_name);
            $privileges = $this->container->db->get('t_privileges');
            if ($this->container->db->count > 0) {
                foreach ($privileges as $privilege) {
                    $vystup_privileg .= '<div>role <strong>'.$privilege['c_role'].'</strong> '.($privilege['c_neg'] == 0?'can':'can not !').' <strong>'.$privilege['c_action'].'</strong> ('.$privilege['c_type'].' type) on table <strong>'.$privilege['c_related_table'].'</strong> <span class="pull-right">[edit, delete]</span></div>';
                }
            }
            else {
                $vystup_privileg .= 'no privileges at the moment';
            }
            
            return $this->container->view->render($response, 'acl/tableprivileges.twig', array('table_name' => $table_name, 'privileges_output' => $vystup_privileg, 'privileges_type' => $privileges_type));
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
        }
        else {
            $this->container->flash->addMessage('info', 'New privilege was added');
        }
        
        // presmerovani podle toho ve kterem formu jsme byli
        $formpage = $request->getParam('formpage');
        if ($formpage == 'user') {
            $user_id = $request->getParam('user_id');
            return $response->withRedirect('/glued/public/acl/userprivileges/'.$user_id);
        }
        else if ($formpage == 'group') {
            $group_id = $request->getParam('group_id');
            return $response->withRedirect('/glued/public/acl/groupprivileges/'.$group_id);
        }
        else if ($formpage == 'role') {
            return $response->withRedirect('/glued/public/acl/roleprivileges');
        }
        else if ($formpage == 'modal') {
            $return_modal_form_uri = $request->getParam('return_modal_form_uri');
            return $response->withRedirect($return_modal_form_uri);
        }
        else {
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
    }
    
    
}
