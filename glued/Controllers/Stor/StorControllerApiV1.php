<?php

namespace Glued\Controllers\Stor;
use Glued\Controllers\Controller;
use Glued\Classes\Stor;
use Glued\Classes\Auth;

class StorControllerApiV1 extends Controller
{
    
    // funkce, ktera vraci prvni radek s dvojteckou
    // davam to do samostatne funkce, protoze to bude pouzite 4x v showFiles a bude to tak prehlednejsi
    private function firstRowUplink($target) {
        return '
                        <li class="item">
                            <div class="item-row">
                                <div class="item-col fixed">
                                    <i class="fa fa-folder-open-o fa-2x"></i>
                                </div>
                                <div class="item-col fixed pull-left item-col-title">
                                    <div class="item-heading">Name</div>
                                    <div>
                                        <a href="" onclick="show_files(\''.$target.'\');return false;" class="">
                                            <h4 class="item-title"> .. </h4>
                                        </a>
                                    </div>
                                </div>
                                <div class="item-col">
                                </div>
                                <div class="item-col">
                                </div>
                                <div class="item-col">
                                </div>
                                <div class="item-col item-col-date">
                                </div>
                                <div class="item-col fixed item-col-actions-dropdown">
                                </div>
                            </div>
                        </li>
            ';
    }
    
    // fukce co vypise prehled souboru v adresari
    public function showFiles($request, $response)
    {
        $vystup = '';
        
        $raw_dirname = $request->getParam('dirname');
        
        // pozor, muze tam byt i id
        $dily = explode('/', $raw_dirname);
        $dirname = $dily[0];
        if (count($dily) > 1) {
            $object_id = $dily[1];
            $mame_id = true;
        }
        else {
            $mame_id = false;
        }
        
        // umisteni
        $vystup .= '<div class="card">';
        if (empty($dirname)) { $vystup .= '<div class="card-block">Nacházíte se v rootu</div>'; }
        else if (!$mame_id) { $vystup .= '<div class="card-block">Nacházíte se v adresáři <strong>'.$this->container->stor->app_dirs[$dirname].'</strong></div>'; }
        else { $vystup .= '<div class="card-block">Nacházíte se v adresáři <strong>'.$this->container->stor->app_dirs[$dirname].' / Object id: '.$object_id.'</strong></div>'; }
        $vystup .= '</div>';
        
        // vrsek vzdy
        $vystup .= '<div class="card items">';
        $vystup .= '<ul class="item-list striped">';
        $vystup .= '
                        <li class="item item-list-header">
                            <div class="item-row">
                                <div class="item-col item-col-header fixed">
                                    <div>
                                        <span>Type</span>
                                    </div>
                                </div>
                                <div class="item-col item-col-header item-col-title">
                                    <div>
                                        <span><i class="fa fa-sort"></i> Name</span>
                                    </div>
                                </div>
                                <div class="item-col item-col-header">
                                    <div>
                                        <span><i class="fa fa-sort"></i> Size</span>
                                    </div>
                                </div>
                                <div class="item-col item-col-header">
                                    <div class="no-overflow">
                                        <span>App</span>
                                    </div>
                                </div>
                                <div class="item-col item-col-header">
                                    <div class="no-overflow">
                                        <span>Owner</span>
                                    </div>
                                </div>
                                <div class="item-col item-col-header item-col-date">
                                    <div>
                                        <span><i class="fa fa-sort"></i> Uploaded</span>
                                    </div>
                                </div>
                                <div class="item-col item-col-header fixed item-col-actions-dropdown"> </div>
                            </div>
                        </li>
        ';
        
        
        
        // vypis diru (s kontrolou ze je dir platny), TODO - udelat poradne
        /*
        fa-folder-o
        fa-folder
        fa-folder-open-o
        fa-folder-open
        */
        // kdyz je prazdny, vypiseme app diry v rootu
        if (empty($dirname)) {
            foreach ($this->container->stor->app_dirs as $dir => $description) {
                if (!$this->container->auth_user->root and $dir == 'users') { continue; }
                
                // u my files tam dame true, jako ze muzeme pridavat
                if ($dir == 'my_files') {
                    $js_kod = 'show_files(\''.$dir.'\', true);';
                }
                else {
                    $js_kod = 'show_files(\''.$dir.'\', false);';
                }
                
                $vystup .= '
                            <li class="item">
                                <div class="item-row">
                                    <div class="item-col fixed">
                                        <i class="fa fa-folder-o fa-2x"></i>
                                    </div>
                                    <div class="item-col fixed pull-left item-col-title">
                                        <div class="item-heading">Name</div>
                                        <div>
                                            <a href="" onclick="'.$js_kod.' return false;" class="">
                                                <h4 class="item-title"> '.$description.' </h4>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="item-col">
                                    </div>
                                    <div class="item-col">
                                    </div>
                                    <div class="item-col">
                                    </div>
                                    <div class="item-col item-col-date">
                                    </div>
                                    <div class="item-col fixed item-col-actions-dropdown">
                                    </div>
                                </div>
                            </li>
                ';
            }
        }
        // kdyz jsme v my_files
        else if ($dirname == 'my_files') {
            // dvojtecka smer nahoru do rootu
            $vystup .= $this->firstRowUplink('');
            
            $table_name = $this->container->stor->app_tables['users'];
            $object_id = $this->container->auth_user->user_id;
            
            // jsem ve svych souborech, takze mam prava na vse
            
            // prehled nahranych souborů pro modul stor
            $sloupce = array("lin.c_uid", "lin.c_owner", "lin.c_filename", "lin.c_inherit_object", "lin.c_ts_created", "obj.sha512", "obj.doc->>'$.data.size' as size", "obj.doc->>'$.data.mime' as mime");
            $this->container->db->join("t_stor_objects obj", "obj.sha512=lin.c_sha512", "LEFT");
            $this->container->db->where("c_inherit_table", $table_name);
            $this->container->db->where("c_inherit_object", $object_id);
            $files = $this->container->db->get('t_stor_links lin', null, $sloupce);
            if (count($files) > 0) {
                foreach ($files as $data) {
                    $action_dropdown = '
                        <div class="item-actions-dropdown">
                            <a class="item-actions-toggle-btn">
                                <span class="inactive">
                                    <i class="fa fa-cog"></i>
                                </span>
                                <span class="active">
                                    <i class="fa fa-chevron-circle-right"></i>
                                </span>
                            </a>
                            <div class="item-actions-block">
                                <ul class="item-actions-list">
                                    <li>
                                        <a class="remove" href="#" data-toggle="modal" data-target="#confirm-modal" onclick="$(\'#file_uid\').val('.$data['c_uid'].');">
                                            <i class="fa fa-trash-o "></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="edit" href="#" data-toggle="modal" data-target="#modal-edit-stor" onclick="$(\'#stor_edit_form_fid\').val('.$data['c_uid'].');var pomucka = $(\'#fname_'.$data['c_uid'].'\').text(); $(\'#stor_edit_form_fname\').val(pomucka);">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="edit" href="#" data-toggle="modal" data-target="#modal-copy-move-stor" onclick="$(\'#stor_copy_move_form_fid\').val('.$data['c_uid'].');">
                                            <i class="fa fa-files-o"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    ';
                    
                    $vystup .= '
                        <li class="item">
                            <div class="item-row">
                                <div class="item-col fixed">
                                    <i class="fa '.$this->container->stor->font_awesome_mime_icon($data['mime']).' fa-2x"></i>
                                </div>
                                <div class="item-col fixed pull-left item-col-title">
                                    <div class="item-heading">Name</div>
                                    <div>
                                        <a href="'.$this->container->router->pathFor('stor.serve.file', ['id' => $data['c_uid'], 'filename' => $data['c_filename']]).'" class="">
                                            <h4 id="fname_'.$data['c_uid'].'" class="item-title">'.$data['c_filename'].'</h4>
                                        </a>
                                    </div>
                                </div>
                                <div class="item-col">
                                    <div class="item-heading">Sales</div>
                                    <div> '.$this->container->stor->human_readable_size($data['size']).' </div>
                                </div>
                                <div class="item-col">
                                    <div class="item-heading">Category</div>
                                    <div class="no-overflow">
                                        
                                    </div>
                                </div>
                                <div class="item-col">
                                    <div class="item-heading">Owner</div>
                                    <div class="no-overflow">
                                        <a href="">'.$this->container->auth->user_screenname($data['c_owner']).'</a>
                                    </div>
                                </div>
                                <div class="item-col item-col-date">
                                    <div class="item-heading">Uploaded</div>
                                    <div class="no-overflow"> '.$data['c_ts_created'].' </div>
                                </div>
                                <div class="item-col fixed item-col-actions-dropdown">
                                    '.$action_dropdown.'
                                </div>
                            </div>
                        </li>
                    ';
                }
            }
        }
        // kdyz jsme v my_owned, bude to mit jine sql cteni
        else if ($dirname == 'my_owned') {
            // dvojtecka smer nahoru do rootu
            $vystup .= $this->firstRowUplink('');
            
            $user_id = $this->container->auth_user->user_id;
            
            // jsem ve svych souborech, takze mam prava na vse
            
            // prehled nahranych souborů pro modul stor
            $sloupce = array("lin.c_uid", "lin.c_owner", "lin.c_filename", "lin.c_inherit_table", "lin.c_inherit_object", "lin.c_ts_created", "obj.sha512", "obj.doc->>'$.data.size' as size", "obj.doc->>'$.data.mime' as mime");
            $this->container->db->join("t_stor_objects obj", "obj.sha512=lin.c_sha512", "LEFT");
            $this->container->db->where("c_owner", $user_id);
            $files = $this->container->db->get('t_stor_links lin', null, $sloupce);
            if (count($files) > 0) {
                foreach ($files as $data) {
                    $dir_path = array_search($data['c_inherit_table'], $this->container->stor->app_tables);
                    $full_path = $dir_path.'/'.$data['c_inherit_object'];
                    
                    $action_dropdown = '
                        <div class="item-actions-dropdown">
                            <a class="item-actions-toggle-btn">
                                <span class="inactive">
                                    <i class="fa fa-cog"></i>
                                </span>
                                <span class="active">
                                    <i class="fa fa-chevron-circle-right"></i>
                                </span>
                            </a>
                            <div class="item-actions-block">
                                <ul class="item-actions-list">
                                    <li>
                                        <a class="remove" href="#" data-toggle="modal" data-target="#confirm-modal" onclick="$(\'#file_uid\').val('.$data['c_uid'].');">
                                            <i class="fa fa-trash-o "></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="edit" href="#" data-toggle="modal" data-target="#modal-edit-stor" onclick="$(\'#stor_edit_form_fid\').val('.$data['c_uid'].');var pomucka = $(\'#fname_'.$data['c_uid'].'\').text(); $(\'#stor_edit_form_fname\').val(pomucka);">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="edit" href="#" data-toggle="modal" data-target="#modal-copy-move-stor" onclick="$(\'#stor_copy_move_form_fid\').val('.$data['c_uid'].');">
                                            <i class="fa fa-files-o"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    ';
                    
                    $vystup .= '
                        <li class="item">
                            <div class="item-row">
                                <div class="item-col fixed">
                                    <i class="fa '.$this->container->stor->font_awesome_mime_icon($data['mime']).' fa-2x"></i>
                                </div>
                                <div class="item-col fixed pull-left item-col-title">
                                    <div class="item-heading">Name</div>
                                    <div>
                                        <a href="'.$this->container->router->pathFor('stor.serve.file', ['id' => $data['c_uid'], 'filename' => $data['c_filename']]).'" class="">
                                            <h4 id="fname_'.$data['c_uid'].'" class="item-title">'.$data['c_filename'].'</h4>
                                        </a>
                                    </div>
                                </div>
                                <div class="item-col">
                                    <div class="item-heading">Sales</div>
                                    <div> '.$this->container->stor->human_readable_size($data['size']).' </div>
                                </div>
                                <div class="item-col">
                                    <div class="item-heading">Category</div>
                                    <div class="no-overflow">
                                        <a href="" onclick="show_files(\''.$full_path.'\', true); return false;">'.$full_path.'</a>
                                    </div>
                                </div>
                                <div class="item-col">
                                    <div class="item-heading">Owner</div>
                                    <div class="no-overflow">
                                        <a href="">'.$this->container->auth->user_screenname($data['c_owner']).'</a>
                                    </div>
                                </div>
                                <div class="item-col item-col-date">
                                    <div class="item-heading">Uploaded</div>
                                    <div class="no-overflow"> '.$data['c_ts_created'].' </div>
                                </div>
                                <div class="item-col fixed item-col-actions-dropdown">
                                    '.$action_dropdown.'
                                </div>
                            </div>
                        </li>
                    ';
                }
            }
        }
        // kdyz nemame id, vypiseme vsechny mozne id jako adresare, tady asi prava nebudou zatim hrat roli
        else if (!$mame_id) {
            // dvojtecka smer nahoru do rootu
            $vystup .= $this->firstRowUplink('');
            
            // pokud zname tabulku, vypiseme jeho id
            if (isset($this->container->stor->app_dirs[$dirname])) {
                if (isset($this->container->stor->app_tables[$dirname])) {
                    // nacteme idecka
                    $cols = Array("c_uid", "stor_name");
                    $this->container->db->orderBy("c_uid","asc");
                    $idecka = $this->container->db->get($this->container->stor->app_tables[$dirname], null, $cols);
                    if ($this->container->db->count > 0) {
                        foreach ($idecka as $idecko) {
                            // TODO, vypsat to nejak srozumitelneji (vyzaduje funkce v kazdem modulu, ktere vypisou nazev, nebo jednotny sloupec s nazvem)
                            // udelame si zatim specialni vetev pro usery
                            if ($dirname == 'users') {
                                $this_screenname = $this->container->auth->user_screenname($idecko['c_uid']);
                                $zobraz_nazev = $idecko['c_uid'].' ['.$this_screenname.']';
                            }
                            else {
                                $zobraz_nazev = $idecko['c_uid'];
                            }
                            
                            $vystup .= '
                                        <li class="item">
                                            <div class="item-row">
                                                <div class="item-col fixed">
                                                    <i class="fa fa-folder-o fa-2x"></i>
                                                </div>
                                                <div class="item-col fixed pull-left item-col-title">
                                                    <div class="item-heading">Name</div>
                                                    <div>
                                                        <a href="" onclick="show_files(\''.$dirname.'/'.$idecko['c_uid'].'\', true); return false;" class="">
                                                            <h4 class="item-title"> '.$idecko['c_uid'].' - '.$idecko['stor_name'].' </h4>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="item-col">
                                                </div>
                                                <div class="item-col">
                                                </div>
                                                <div class="item-col">
                                                </div>
                                                <div class="item-col item-col-date">
                                                </div>
                                                <div class="item-col fixed item-col-actions-dropdown">
                                                </div>
                                            </div>
                                        </li>
                            ';
                        }
                    }
                }
                else {
                    $vystup .= '
                                <li class="item">
                                    <div class="item-row">
                                        chyba, tento dir nelze vypsat
                                    </div>
                                </li>
                    ';
                }
            }
            else {
                $vystup .= '
                            <li class="item">
                                <div class="item-row">
                                    chyba, tento dir neexistuje
                                </div>
                            </li>
                ';
            }
        }
        // pokud mame id, vypiseme teprve soubory s ohledem na prava
        else {
            // dvojtecka smer nahoru do dirname
            $vystup .= $this->firstRowUplink($dirname);
            
            // kdyz existuje, vypiseme dvojtecku a soubory
            // kdyz neexistuje vypiseme dvojtecku a nejakou chybu
            if (isset($this->container->stor->app_dirs[$dirname])) {
                // PRAVA (pokud mame hardcodovanou tabulku pro adresar), objektove id mame v $object_id
                $acl_tabulka = $this->container->stor->app_tables[$dirname];
                // tady jsme uz v objektu v podstate, prava by se mela odvozovat od toho objektu
                $allowed_global_actions = array();
                if ($this->container->permissions->have_action_on_object($acl_tabulka, $object_id, 'list')) { $allowed_global_actions[] = 'list'; }
                if ($this->container->permissions->have_action_on_object($acl_tabulka, $object_id, 'read')) { $allowed_global_actions[] = 'read'; }
                if ($this->container->permissions->have_action_on_object($acl_tabulka, $object_id, 'write')) { $allowed_global_actions[] = 'write'; }
                if ($this->container->permissions->have_action_on_object($acl_tabulka, $object_id, 'delete')) { $allowed_global_actions[] = 'delete'; }
                
                // jestli to vubec vypsat
                if (in_array('list', $allowed_global_actions)) {
                    
                    // prehled nahranych souborů pro objekt v modulu stor
                    $sloupce = array("lin.c_uid", "lin.c_owner", "lin.c_filename", "lin.c_inherit_object", "lin.c_ts_created", "obj.sha512", "obj.doc->>'$.data.size' as size", "obj.doc->>'$.data.mime' as mime");
                    $this->container->db->join("t_stor_objects obj", "obj.sha512=lin.c_sha512", "LEFT");
                    $this->container->db->where("c_inherit_table", $acl_tabulka);
                    $this->container->db->where("c_inherit_object", $object_id);
                    $files = $this->container->db->get('t_stor_links lin', null, $sloupce);
                    if (count($files) > 0) {
                        foreach ($files as $data) {
                            // je mozne ziskat link na soubor
                            $je_mozne_read = false;
                            if (in_array('read', $allowed_global_actions)) { $je_mozne_read = true; }
                            // je mozne editovat (write)
                            $je_mozne_write = false;
                            if (in_array('write', $allowed_global_actions)) { $je_mozne_write = true; }
                            
                            $action_dropdown = '';
                            if ($je_mozne_write or $je_mozne_delete) {
                                $action_dropdown .= '
                                    <div class="item-actions-dropdown">
                                        <a class="item-actions-toggle-btn">
                                            <span class="inactive">
                                                <i class="fa fa-cog"></i>
                                            </span>
                                            <span class="active">
                                                <i class="fa fa-chevron-circle-right"></i>
                                            </span>
                                        </a>
                                        <div class="item-actions-block">
                                            <ul class="item-actions-list">';
                                if ($je_mozne_write) {  // smazani souboru neni delete pravo na objekt, ale write pravo, protoze soubory nejsou objekty samy o sobe, ale jen pridavky k hlavnimu objektu o jehoz prava tady jde
                                    $action_dropdown .= '
                                                <li>
                                                    <a class="remove" href="#" data-toggle="modal" data-target="#confirm-modal" onclick="$(\'#file_uid\').val('.$data['c_uid'].');">
                                                        <i class="fa fa-trash-o"></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="edit" href="#" data-toggle="modal" data-target="#modal-edit-stor" onclick="$(\'#stor_edit_form_fid\').val('.$data['c_uid'].');var pomucka = $(\'#fname_'.$data['c_uid'].'\').text(); $(\'#stor_edit_form_fname\').val(pomucka);">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="edit" href="#" data-toggle="modal" data-target="#modal-copy-move-stor" onclick="$(\'#stor_copy_move_form_fid\').val('.$data['c_uid'].');">
                                                        <i class="fa fa-files-o"></i>
                                                    </a>
                                                </li>
                                                ';
                                }
                                $action_dropdown .= '
                                            </ul>
                                        </div>
                                    </div>
                                ';
                            }
                            
                            $vystup .= '
                                <li class="item">
                                    <div class="item-row">
                                        <div class="item-col fixed">
                                            <i class="fa '.$this->container->stor->font_awesome_mime_icon($data['mime']).' fa-2x"></i>
                                        </div>
                                        <div class="item-col fixed pull-left item-col-title">
                                            <div class="item-heading">Name</div>
                                            <div>
                                                '.($je_mozne_read?'
                                                <a href="'.$this->container->router->pathFor('stor.serve.file', ['id' => $data['c_uid'], 'filename' => $data['c_filename']]).'" class="">
                                                    <h4 id="fname_'.$data['c_uid'].'" class="item-title">'.$data['c_filename'].'</h4>
                                                </a>
                                                ':'
                                                <h4 id="fname_'.$data['c_uid'].'" class="item-title">'.$data['c_filename'].'</h4>
                                                ').'
                                            </div>
                                        </div>
                                        <div class="item-col">
                                            <div class="item-heading">Sales</div>
                                            <div> '.$this->container->stor->human_readable_size($data['size']).' </div>
                                        </div>
                                        <div class="item-col">
                                            <div class="item-heading">Category</div>
                                            <div class="no-overflow">
                                                
                                            </div>
                                        </div>
                                        <div class="item-col">
                                            <div class="item-heading">Owner</div>
                                            <div class="no-overflow">
                                                <a href="">'.$this->container->auth->user_screenname($data['c_owner']).'</a>
                                            </div>
                                        </div>
                                        <div class="item-col item-col-date">
                                            <div class="item-heading">Uploaded</div>
                                            <div class="no-overflow"> '.$data['c_ts_created'].' </div>
                                        </div>
                                        <div class="item-col fixed item-col-actions-dropdown">
                                            '.$action_dropdown.'
                                        </div>
                                    </div>
                                </li>
                            ';
                        }
                    }
                }
                else {
                    $vystup .= '
                                <li class="item">
                                    <div class="item-row">
                                        nemate pravo videt vypis souboru v tomto adresari
                                    </div>
                                </li>
                    ';
                }
            }
            else {
                $vystup .= '
                            <li class="item">
                                <div class="item-row">
                                    chyba, tento dir neexistuje
                                </div>
                            </li>
                ';
            }
        }
        $vystup .= '</ul>';
        $vystup .= '</div>';
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($vystup);
        return $response;
    }
    
    // prehled odpovidajicich objektu do modal popupu pro kopirovani
    public function showModalObjects($request, $response) {
        $vystup = '';
        
        $dirname = $request->getParam('dirname');
        
        if (isset($this->container->stor->app_dirs[$dirname])) {
            if (isset($this->container->stor->app_tables[$dirname])) {
                // nacteme idecka
                $cols = Array("c_uid", "stor_name");
                $this->container->db->orderBy("c_uid","asc");
                $idecka = $this->container->db->get($this->container->stor->app_tables[$dirname], null, $cols);
                if ($this->container->db->count > 0) {
                    foreach ($idecka as $idecko) {
                        $vystup .= '<option value="'.$idecko['c_uid'].'">'.$idecko['c_uid'].' - '.$idecko['stor_name'].'</option>';
                    }
                }
            }
        }
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($vystup);
        return $response;
    }
}
