<?php
namespace Glued\Controllers\Permissions;

use Glued\Controllers\Controller;   // kvuli extends

class PermissionsController extends Controller
{
    
    // zakladni stranka pro kazdeho, kde uvidi sve prava
    public function getMyAcl($request, $response)
    {
        // odkaz na developer stranku
        $developer_link = '';
        $my_user_data = $this->container->auth->user();
        $my_groups = $this->container->permissions->user_groups($my_user_data);
        if (in_array('root', $my_groups)) {
            $developer_link = '<a class="pull-right" href="'.$this->container->router->pathFor('acl.developer').'">for Developer</a><a class="pull-right" style="margin: 0 25px;" href="'.$this->container->router->pathFor('acl.crossroad').'">for Root</a>';
        }
        
        
        $vystup_users = '';
        $this->container->db->where("c_role", 'user');
        $this->container->db->where("c_who", $my_user_data['c_uid']);
        $privileges = $this->container->db->get('t_privileges');
        if ($this->container->db->count > 0) {
            foreach ($privileges as $privilege) {
                $vystup_users .= '<div id="privilege_row_'.$privilege['c_id'].'">you '.($privilege['c_neg'] == 0?'can':'can not !').' <strong>'.$privilege['c_action'].'</strong> ('.$privilege['c_type'].' type) on table <strong>'.$privilege['c_related_table'].'</strong></div>';
            }
        }
        else {
            $vystup_users .= 'no privileges at the moment';
        }
        
        
        
        $groups_array = $this->container->permissions->show_groups();
        $vystup_groups = '';
        foreach($groups_array as $key => $value) {
            if (in_array($key, $my_groups)) {
                $this->container->db->where("c_role", 'group');
                $this->container->db->where("c_who", $value);
                $privileges = $this->container->db->get('t_privileges');
                if ($this->container->db->count > 0) {
                    foreach ($privileges as $privilege) {
                        $vystup_groups .= '<div id="privilege_row_'.$privilege['c_id'].'">group <strong>'.$key.'</strong> '.($privilege['c_neg'] == 0?'can':'can not !').' <strong>'.$privilege['c_action'].'</strong> ('.$privilege['c_type'].' type) on table <strong>'.$privilege['c_related_table'].'</strong></div>';
                    }
                }
            }
        }
        if (empty($vystup_groups)) {
            $vystup_groups .= 'no privileges at the moment';
        }
        
        $prislusnost_groups = implode(', ', $my_groups);
        
        return $this->container->view->render($response, 'permissions/mypermissions.twig', 
            array(
                'developer_link' => $developer_link,
                'vystup_users' => $vystup_users,
                'vystup_groups' => $vystup_groups,
                'prislusnost_groups' => $prislusnost_groups
            ));
    }
    
    // shows basic page with acl options for root
    public function getAclCrossroad($request, $response)
    {
        // users
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
        
        // groups
        $groups_array = $this->container->permissions->show_groups();
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
        
        // tabulky
        $vystup_tables = '
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Table privileges</th>
                            <th>Global privileges</th>
                        </tr>
                    </thead>
                    <tbody>';
        $tables_array = $this->container->permissions->show_tables();
        foreach ($tables_array as $table_name) {
            $vystup_tables .= '
                        <tr>
                            <th scope="row">'.$table_name.'</th>
                            <td><a href="tableprivileges/'.$table_name.'">view</a></td>
                            <td><a href="globalprivileges/'.$table_name.'">view</a></td>
                        </tr>';
        }
        $vystup_tables .= '
                    </tbody>
                </table>
        ';
        
        return $this->container->view->render($response, 'permissions/crossroads.twig', array(
            'vystup_users' => $vystup_users,
            'vystup_groups' => $vystup_groups,
            'vystup_tables' => $vystup_tables ));
    }
    
    // shows advanced acl settings for developer
    public function getAclDeveloper($request, $response)
    {
        // actions
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
        
        // pridane role
        $vystup_roles = '';
        $role = $this->container->db->get('t_implemented_roles');
        if (count($role) > 0) {
            foreach ($role as $role1) {
                $vystup_roles .= '<div><strong>'.$role1['c_role'].'</strong></div>';
            }
        }
        
        $vystup_hardcoded_permissions = '<div>Permissions: <br />'.print_r($this->container->permissions->show_permissions(), true).'</div>';
        $vystup_hardcoded_groups = '<div>Groups: <br />'.print_r($this->container->permissions->show_groups(), true).'</div>';
        $vystup_hardcoded_statuses = '<div>Statuses: <br />'.print_r($this->container->permissions->show_statuses(), true).'</div>';
        $vystup_hardcoded_tables = '<div>Tables: <br />'.print_r($this->container->permissions->show_tables(), true).'</div>';
        
        return $this->container->view->render($response, 'permissions/developers.twig', array(
            'vystup_hardcoded_permissions' => $vystup_hardcoded_permissions,
            'vystup_hardcoded_groups' => $vystup_hardcoded_groups,
            'vystup_hardcoded_statuses' => $vystup_hardcoded_statuses,
            'vystup_hardcoded_tables' => $vystup_hardcoded_tables,
            'vystup_actions' => $vystup_actions,
            'vystup_roles' => $vystup_roles ));
    }
    
    // post for add action from crossroads form
    public function postAddActionRole($request, $response) {
        // pro action
        if ($request->getParam('what') == 'action') {
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
                    return $response->withRedirect($this->container->router->pathFor('acl.developer'));
                }
                else {
                    $this->container->flash->addMessage('info', 'New action was added');
                    return $response->withRedirect($this->container->router->pathFor('acl.developer'));
                }
            }
            else {
                $this->container->flash->addMessage('info', 'you left title empty !');
                return $response->withRedirect($this->container->router->pathFor('acl.developer'));
            }
        }
        else if ($request->getParam('what') == 'role') {
            // nazev musi byt vyplnen
            if (!empty($request->getParam('title'))) {
                
                $title = $request->getParam('title');
                
                $data = Array ("c_role" => $title );
                
                $insert = $this->container->db->insert('t_implemented_roles', $data);
                
                if (!$insert) {
                    $this->container->logger->warn("Role was not added. DB error.");
                    return $response->withRedirect($this->container->router->pathFor('acl.developer'));
                }
                else {
                    $this->container->flash->addMessage('info', 'New role was added');
                    return $response->withRedirect($this->container->router->pathFor('acl.developer'));
                }
            }
            else {
                $this->container->flash->addMessage('info', 'you left title empty !');
                return $response->withRedirect($this->container->router->pathFor('acl.developer'));
            }
        }
    }
    
    // shows edit page for groups membership of one user
    public function getUserGroups($request, $response, $args)
    {
        
        $this->container->db->where("c_uid", $args['id']);
        $user = $this->container->db->getOne('t_users');
        
        $groups_array = $this->container->permissions->show_groups();
        
        $checkboxes = '';
        foreach ($groups_array as $kk => $vv) {
            $checkboxes .= '
                <div>
                    <label>
                        <input class="checkbox" type="checkbox" name="chb_'.$kk.'" value="'.$vv.'" '.(($user['c_group_mememberships'] & $vv)?' checked="checked" ':'').'>
                        <span>'.$kk.'</span>
                    </label>
                </div>
            ';
        }
        
        return $this->container->view->render($response, 'permissions/usergroups.twig', array(
        'user' => $user,
        'checkboxes' => $checkboxes));
    }
    
    // sets new membership for user
    public function postUserGroups($request, $response)
    {
        $user_id = $request->getParam('user_id');
        $groups_array = $this->container->permissions->show_groups();
        
        if ($user_id) {
            
            // vypocitame novy group membership
            $group_sum = 0;
            foreach ($groups_array as $kk => $vv) {
                if (!empty($request->getParam('chb_'.$kk))) { $group_sum += $vv; }
            }
            
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
        $permissions_array = $this->container->permissions->show_permissions();
        
        foreach ($permissions_array as $i => $k) {
            if ($user['c_unixperms'] & $k) { $permissions[$i] = true; }
            else { $permissions[$i] = false; }
        }
        
        return $this->container->view->render($response, 'permissions/userunix.twig', array('user' => $user, 'permissions' => $permissions));
    }
    
    // sets new unixperms for user
    public function postUserUnix($request, $response)
    {
        $user_id = $request->getParam('user_id');
        $permissions_array = $this->container->permissions->show_permissions();
        
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
        
        // nacteme mozne tabulky
        $table_options = '';
        if ($this->container->db->count > 0) {
            $tables_array = $this->container->permissions->show_tables();
            foreach ($tables_array as $table_name) {
                $table_options .= '<option value="'.$table_name.'">'.$table_name.'</option>';
            }
        }
        
        return $this->container->view->render($response, 'permissions/userprivileges.twig', 
            array(
                'user' => $user,
                'privileges_output' => $vystup_privileg,
                'action_options' => $action_options,
                'table_options' => $table_options,
                'additional_javascript' => $additional_javascript
            ));
    }
    
    // shows edit page for module acl of one group - table t_privileges
    public function getGroupPrivileges($request, $response, $args)
    {
        $groups_array = $this->container->permissions->show_groups();
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
            
            // nacteme mozne tabulky
            $table_options = '';
            if ($this->container->db->count > 0) {
                $tables_array = $this->container->permissions->show_tables();
                foreach ($tables_array as $table_name) {
                    $table_options .= '<option value="'.$table_name.'">'.$table_name.'</option>';
                }
            }
            
            return $this->container->view->render($response, 'permissions/groupprivileges.twig', 
                array(
                    'group_name' => $group_name,
                    'group_id' => $group_id,
                    'privileges_output' => $vystup_privileg,
                    'action_options' => $action_options,
                    'table_options' => $table_options
                ));
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
        
        // nacteme si role
        $role_options = '';
        $roles = $this->container->db->get('t_implemented_roles');
        if ($this->container->db->count > 0) {
            foreach ($roles as $role1) {
                $role_options .= '<option value="'.$role1['c_role'].'">'.$role1['c_role'].'</option>';
            }
        }
        
        // nacteme si mozne akce
        $action_options = '';
        $akce = $this->container->db->get('t_action');
        if ($this->container->db->count > 0) {
            foreach ($akce as $akce1) {
                $action_options .= '<option value="'.$akce1['c_title'].'">'.$akce1['c_title'].' ('.($akce1['c_apply_object'] == 1?'object':'table').')</option>';
            }
        }
        
        // nacteme mozne tabulky
        $table_options = '';
        if ($this->container->db->count > 0) {
            $tables_array = $this->container->permissions->show_tables();
            foreach ($tables_array as $table_name) {
                $table_options .= '<option value="'.$table_name.'">'.$table_name.'</option>';
            }
        }
        
        return $this->container->view->render($response, 'permissions/roleprivileges.twig', 
            array(
                'user' => $user,
                'privileges_output' => $vystup_privileg,
                'action_options' => $action_options,
                'table_options' => $table_options,
                'role_options' => $role_options
            ));
    }
    
    // shows edit page for make rows in implemented_actions table (akce + status + tabulka)
    public function getImplementedActions($request, $response)
    {
        $this->container->db->where("c_uid", $args['id']);
        $user = $this->container->db->getOne('t_users');
        
        $statuses = $this->container->permissions->show_statuses();
        
        $vystup_privileg = '';
        $privileges = $this->container->db->get('t_implemented_action');
        if ($this->container->db->count > 0) {
            foreach ($privileges as $privilege) {
                $pole_pouzitych_statusu = array();
                foreach ($statuses as $kk => $vv) {
                    if ($privilege['c_status'] & $vv) { $pole_pouzitych_statusu[] = $kk; }
                }
                $vystup_privileg .= '<div>use action <strong>'.$privilege['c_action'].'</strong> on table <strong>'.$privilege['c_table'].'</strong> when status is <strong>'.implode(', ', $pole_pouzitych_statusu).'</strong> <span class="pull-right">[edit, delete]</span></div>';
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
        
        // nacteme mozne tabulky
        $table_options = '';
        if ($this->container->db->count > 0) {
            $tables_array = $this->container->permissions->show_tables();
            foreach ($tables_array as $table_name) {
                $table_options .= '<option value="'.$table_name.'">'.$table_name.'</option>';
            }
        }
        
        // nacteme mozne statusy
        $status_options = '';
        $status_array = $this->container->permissions->show_statuses();
        foreach ($status_array as $kk => $vv) {
            $status_options .= '<option value="'.$vv.'">'.$kk.'</option>';
        }
        
        return $this->container->view->render($response, 'permissions/implementedactions.twig', 
            array(
                'user' => $user,
                'privileges_output' => $vystup_privileg,
                'action_options' => $action_options,
                'table_options' => $table_options,
                'status_options' => $status_options
            ));
    }
    
    // shows edit page who has table privileges for one table - table t_privileges
    public function getTableTablePrivileges($request, $response, $args)
    {
        $allowed_tables_array = $this->container->permissions->show_tables();
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
            
            return $this->container->view->render($response, 'permissions/tableprivileges.twig', array('table_name' => $table_name, 'privileges_output' => $vystup_privileg, 'privileges_type' => $privileges_type));
        }
        else {
            $this->container->flash->addMessage('info', 'bad table name');
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
    }
    
    // shows edit page who has global privileges for one table - table t_privileges
    public function getGlobalTablePrivileges($request, $response, $args)
    {
        $allowed_tables_array = $this->container->permissions->show_tables();
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
            
            return $this->container->view->render($response, 'permissions/tableprivileges.twig', array('table_name' => $table_name, 'privileges_output' => $vystup_privileg, 'privileges_type' => $privileges_type));
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
            return $response->withRedirect('/glued/public/permissions/userprivileges/'.$user_id);
        }
        else if ($formpage == 'group') {
            $group_id = $request->getParam('group_id');
            return $response->withRedirect('/glued/public/permissions/groupprivileges/'.$group_id);
        }
        else if ($formpage == 'role') {
            return $response->withRedirect('/glued/public/permissions/roleprivileges');
        }
        else if ($formpage == 'modal') {
            $return_modal_form_uri = $request->getParam('return_modal_form_uri');
            return $response->withRedirect($return_modal_form_uri);
        }
        else {
            return $response->withRedirect($this->container->router->pathFor('acl.crossroad'));
        }
    }
    
    // insert new rule in implemented action
    public function postNewImplementedAction($request, $response) {
        
        $action = $request->getParam('action');
        $related_table = $request->getParam('related_table');
        $statuses = $request->getParam('statuses');
        
        if (empty($statuses) or count($statuses) == 0) {
            $this->container->flash->addMessage('info', 'You left statuses empty.');
        }
        else {
            $status_sum = 0;
            foreach ($statuses as $vv) {
                $status_sum += $vv;
            }
            
            $data = Array ("c_table" => $related_table, "c_action" => $action, "c_status" => $status_sum );
            
            $insert = $this->container->db->insert('t_implemented_action', $data);
            
            if (!$insert) {
                $this->container->logger->warn("Combination was not added. DB error.");
            }
            else {
                $this->container->flash->addMessage('info', 'New combination was added');
            }
        }
        
        return $response->withRedirect($this->container->router->pathFor('acl.implementedactions'));
    }
    
}
