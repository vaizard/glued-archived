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
    
    // vraci moznosti do select2 podle toho co je zatim napsane. vystup je v jsonu, pole objektu
    public function showFilterOptions($request, $response) {
        $vystup = '';
        
        $term = $request->getParam('term');
        
        // podle toho cim to zacne
        // lomitko = adresar, @ = uzivatel, # = tag
        $prvni_znak = mb_substr($term, 0, 1, 'utf-8');
        
        if ($term == '//') {
            $vystup .= '
    {
      "results": [
        {
          "id": "//",
          "text": "//apps"
        }
      ]
    }
            ';
        }
        else if ($prvni_znak == '/') {   // adresare
            $casti = explode('/', $term);
            $app_cast = $casti[1];
            // zjistime kolika odpovida apps
            $objekty_adresaru = array();
            $posledni_dir = '';
            foreach ($this->container->stor->app_dirs as $dir => $description) {
                if (!isset($this->container->stor->app_tables[$dir])) { continue; }
                if (empty($app_cast) or substr_count($dir, $app_cast) > 0) {
                    $objekty_adresaru[] = '
        {
          "id": "/'.$dir.'",
          "text": "/'.$dir.'"
        }
                    ';
                    $posledni_dir = $dir;
                }
            }
            
            $vystup .= '
{
  "results": [
            ';
            if (isset($casti[2]) and count($objekty_adresaru) == 1) { // je tam druhe lomitko, nabidneme teda objekty
                $objekt_cast = $casti[2];
                $cols = Array("c_uid", "stor_name");
                $this->container->db->orderBy("c_uid","asc");
                $idecka = $this->container->db->get($this->container->stor->app_tables[$posledni_dir], null, $cols);
                if ($this->container->db->count > 0) {
                    $objekty_modulu = array();
                    foreach ($idecka as $idecko) {
                        $nazev = $idecko['c_uid'].' - '.$idecko['stor_name'];
                        if (empty($objekt_cast) or substr_count(mb_strtolower($nazev, 'utf-8'), mb_strtolower($objekt_cast, 'utf-8')) > 0) {
                            $objekty_modulu[] = '
            {
              "id": "/'.$posledni_dir.'/'.$idecko['c_uid'].'",
              "text": "/'.$posledni_dir.'/'.$nazev.'"
            }
                            ';
                        }
                    }
                    $vystup .= implode(',', $objekty_modulu);
                }
            }
            else {  // je tam jen jedno lomitko, nabidneme appy
                $vystup .= implode(',', $objekty_adresaru);
            }
            $vystup .= '
  ]
}
            ';
        }
        else if ($prvni_znak == '@') {  // uzivatele
            // nacteme idecka
            $cols = Array("c_uid", "stor_name");
            $this->container->db->orderBy("c_uid","asc");
            $idecka = $this->container->db->get('t_users', null, $cols);
            $objekty_useru = array();
            if ($this->container->db->count > 0) {
                foreach ($idecka as $idecko) {
                    $objekty_useru[] = '
    {
      "id": "@'.$idecko['c_uid'].'",
      "text": "@'.$idecko['c_uid'].' - '.$idecko['stor_name'].'"
    }
                    ';
                }
            }
            $vystup .= '
    {
      "results": [';
        $vystup .= implode(',', $objekty_useru);
        $vystup .= '
      ]
    }
            ';
        }
        else if ($prvni_znak == '#') {  // tagy, zatim nemame tabulku
            $vystup .= '
    {
      "results": [
        {
          "id": "#tag1",
          "text": "#tag1"
        },
        {
          "id": "#tag1",
          "text": "#tag2"
        }
      ]
    }
            ';
        }
        else {  // musime vratit prazdne pole, aby to bylo validni
            $vystup .= '
    {
      "results": [ ]
    }
            ';
        }
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($vystup);
        return $response;
    }
    
    
    // fukce co vypise prehled filtrovanych souboru v adresari
    public function showFilteredFiles($request, $response)
    {
        $vystup = '';
        $uploader = '';
        
        $raw_filters = $request->getParam('filters');
        $orderby = $request->getParam('orderby');
        $direction = $request->getParam('direction');
        $page = $request->getParam('page');
        
        // dekodujeme na pole
        $filters = json_decode($raw_filters, true);
        
        $vystup .= '<div>filtrovaci json: '.$raw_filters.', orderby: '.$orderby.', direction: '.$direction.', page: '.$page.'</div>';
        
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
                                <div class="item-col item-col-header item-col-title" onclick="filter_stor_files(\'name\', \''.(($orderby == 'name' and $direction == 'asc')?'desc':'asc').'\', 1);">
                                    <div>
                                        <span><i class="fa fa-sort"></i> Name</span>
                                    </div>
                                </div>
                                <div class="item-col item-col-header" onclick="filter_stor_files(\'size\', \''.(($orderby == 'size' and $direction == 'asc')?'desc':'asc').'\', 1);">
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
                                <div class="item-col item-col-header item-col-date" onclick="filter_stor_files(\'uploaded\', \''.(($orderby == 'uploaded' and $direction == 'asc')?'desc':'asc').'\', 1);">
                                    <div>
                                        <span><i class="fa fa-sort"></i> Uploaded</span>
                                    </div>
                                </div>
                                <div class="item-col item-col-header fixed item-col-actions-dropdown"> </div>
                            </div>
                        </li>
        ';
        
        // zanalyzujeme si co mame zadane ($filters)
        if (count($filters) > 0) {
            $je_tam_apps = false;
            $jsou_tam_objekty = false;
            $objektovy_dir = '';
            $pole_adresaru = array();
            $pole_useru = array();
            $pole_tagu = array();
            $pole_nazvu = array();
            
            $bude_uploader = false;
            $uploader_path = '';
            
            foreach ($filters as $filter) {
                // podle toho cim to zacne
                // lomitko = adresar, @ = uzivatel, # = tag
                $prvni_znak = mb_substr($filter, 0, 1, 'utf-8');
                
                // nejdriv detekujeme pritomnost apps //
                if ($filter == '//') {
                    $je_tam_apps = true;
                }
                // lomitkovy filtr by mel byt jen jeden, pokud bude vic, vezmeme jen prvni (a nebo to muzeme vzit jako or?)
                else if ($prvni_znak == '/') {
                    $pole_adresaru[] = $filter; // dame to tam cele, protoze pak to teprve budeme delit na lomitka a muze tam byt i objekt
                    // zjistime, jestli to nahodou neni pozadavek na vypis objektu z app
                    foreach ($this->container->stor->app_dirs as $dir => $description) {
                        if (!isset($this->container->stor->app_tables[$dir])) { continue; }
                        if ($filter == '/'.$dir.'/') {
                            $jsou_tam_objekty = true;
                            $objektovy_dir = $dir;
                        }
                    }
                }
                // ownerovy filtr muze byt taky jen jeden, taky vemem jen prvni (a nebo to muzeme vzit jako or?)
                else if ($prvni_znak == '@') {
                    $pole_useru[] = mb_substr($filter, 1, null, 'utf-8');
                }
                // tagovych filtru muze byt vic
                else if ($prvni_znak == '#') {
                    $pole_tagu[] = mb_substr($filter, 1, null, 'utf-8');
                }
                // nazvovych filtru muze byt vic
                else {
                    $pole_nazvu[] = $filter;
                }
            }
            
            // pokud je tam apps, vypiseme jen apps
            if ($je_tam_apps) {
                foreach ($this->container->stor->app_dirs as $dir => $description) {
                    if (!isset($this->container->stor->app_tables[$dir])) { continue; }
                    $vystup .= '
                        <li class="item">
                            <div class="item-row">
                                <div class="item-col fixed">
                                </div>
                                <div class="item-col fixed pull-left item-col-title">
                                    <div class="item-heading">Name</div>
                                    <div>
                                        <a href="" class="stor-shortcuts" data-id="/'.$dir.'/" data-text="/'.$dir.'/">
                                            <h4 class="item-title">/'.$dir.'/</h4>
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
            else if ($jsou_tam_objekty) {
                // nacteme idecka
                $cols = Array("c_uid", "stor_name");
                $this->container->db->orderBy("c_uid","asc");
                $idecka = $this->container->db->get($this->container->stor->app_tables[$objektovy_dir], null, $cols);
                if ($this->container->db->count > 0) {
                    foreach ($idecka as $idecko) {
                        $vystup .= '
                                    <li class="item">
                                        <div class="item-row">
                                            <div class="item-col fixed">
                                                <i class="fa fa-folder-o fa-2x"></i>
                                            </div>
                                            <div class="item-col fixed pull-left item-col-title">
                                                <div class="item-heading">Name</div>
                                                <div>
                                                    <a href="" class="stor-shortcuts" data-id="/'.$objektovy_dir.'/'.$idecko['c_uid'].'" data-text="/'.$objektovy_dir.'/'.$idecko['c_uid'].' - '.$idecko['stor_name'].'">
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
                // krome tagovych muzeme udelat vyber jednim sql dotazem nad links tabulkou
                $sloupce = array("lin.c_uid", "lin.c_owner", "lin.c_filename", "lin.c_inherit_table", "lin.c_inherit_object", "lin.c_ts_created", "obj.sha512", "obj.doc->>'$.data.size' as size", "obj.doc->>'$.data.mime' as mime");
                $this->container->db->join("t_stor_objects obj", "obj.sha512=lin.c_sha512", "LEFT");
                if (count($pole_adresaru) > 0) {
                    $casti = explode('/', $pole_adresaru[0]);
                    $inherit_table = $this->container->stor->app_tables[$casti[1]];
                    $this->container->db->where("c_inherit_table", $inherit_table);
                    if (!empty($casti[2])) {
                        $this->container->db->where("c_inherit_object", $casti[2]);
                        // je tam adresar i objekt, muzeme ukazat uploadovaci form
                        $bude_uploader = true;
                        $uploader_path = $casti[1].'/'.$casti[2];
                    }
                }
                if (count($pole_useru) > 0) {
                    $this->container->db->where("c_owner", $pole_useru[0]);
                }
                if (count($pole_tagu) > 0) {
                    // tagy zatim nemame
                }
                if (count($pole_nazvu) > 0) {
                    $this->container->db->where("c_filename", '%'.$pole_nazvu[0].'%', 'like');
                }
                // oderby
                if ($orderby == 'name') {
                    $this->container->db->orderBy("c_filename", $direction);
                }
                else if ($orderby == 'size') {
                    $this->container->db->orderBy("size", $direction);
                }
                else if ($orderby == 'uploaded') {
                    $this->container->db->orderBy("c_ts_created", $direction);
                }
                
                $files = $this->container->db->get('t_stor_links lin', null, $sloupce);
                if (count($files) > 0) {
                    foreach ($files as $data) {
                        $dir_path = array_search($data['c_inherit_table'], $this->container->stor->app_tables);
                        $full_path = $dir_path.'/'.$data['c_inherit_object'];
                        
                        // urcime jake mame globalni prava na inherit objekt (ne na soubor ale na objekt ve kterem soubor je)
                        // TODO, pole s nactenymi pravy by melo byt globalni, aby se necetly stejna prava u kazdeho dalsiho souboru se stejnou dvojici table objekt
                        $allowed_global_actions = array();
                        if ($this->container->permissions->have_action_on_object($data['c_inherit_table'], $data['c_inherit_object'], 'list')) { $allowed_global_actions[] = 'list'; }
                        if ($this->container->permissions->have_action_on_object($data['c_inherit_table'], $data['c_inherit_object'], 'read')) { $allowed_global_actions[] = 'read'; }
                        if ($this->container->permissions->have_action_on_object($data['c_inherit_table'], $data['c_inherit_object'], 'write')) { $allowed_global_actions[] = 'write'; }
                        
                        // jestli soubor vubec vylistovat, TODO tohle je ale spis read pravo na objekt. ne list.
                        if (in_array('list', $allowed_global_actions)) {
                            
                            $action_dropdown = '';
                            // jestli bude ozubene kolo
                            if (in_array('write', $allowed_global_actions)) {
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
                                                    <a class="remove" href="#" data-toggle="modal" data-target="#confirm-modal" onclick="$(\'#delete_file_uid\').val('.$data['c_uid'].');">
                                                        <i class="fa fa-trash-o "></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="edit" href="#" data-toggle="modal" data-target="#modal-edit-stor" onclick="$(\'#edit_file_uid\').val('.$data['c_uid'].');var pomucka = $(\'#fname_'.$data['c_uid'].'\').text(); $(\'#edit_file_fname\').val(pomucka);">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="edit" href="#" data-toggle="modal" data-target="#modal-copy-move-stor" onclick="$(\'#copy_move_file_uid\').val('.$data['c_uid'].');">
                                                        <i class="fa fa-files-o"></i>
                                                    </a>
                                                </li>
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
                                                '.(in_array('read', $allowed_global_actions)?'
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
                                                <a href="" class="stor-shortcuts" data-id="/'.$full_path.'" data-text="/'.$full_path.'">
                                                    /'.$full_path.'
                                                </a>
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
            }
        }
        else {
            $your_user_id = $this->container->auth_user->user_id;
            $your_screenname = $this->container->auth->user_screenname($your_user_id);
            $vystup .= '
                <li class="item">
                    <div class="item-row">
                        <div class="item-col fixed">
                        </div>
                        <div class="item-col fixed pull-left item-col-title">
                            <div class="item-heading">Name</div>
                            <div>
                                <a href="" class="stor-shortcuts" data-id="@'.$your_user_id.'" data-text="@'.$your_user_id.' - '.$your_screenname.'">
                                    <h4 class="item-title">My files</h4>
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
                <li class="item">
                    <div class="item-row">
                        <div class="item-col fixed">
                        </div>
                        <div class="item-col fixed pull-left item-col-title">
                            <div class="item-heading">Name</div>
                            <div>
                                <a href="" class="stor-shortcuts" data-id="//" data-text="//apps">
                                    <h4 class="item-title">Apps</h4>
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
        
        $vystup .= '</ul>';
        $vystup .= '</div>';
        
        // textovy vystup ajaxu nejdriv vyrenderujeme pres view, aby se tam dosadilo csrf pres middleware
        if ($bude_uploader) {
            return $this->container->view->render($response, 'stor/partials/filter-with-upload.twig',
            array(
                'vystup' => $vystup,
                'uploader_path' => $uploader_path
            ));
        }
        else {
            return $this->container->view->render($response, 'stor/partials/filter-without-upload.twig',
            array(
                'vystup' => $vystup
            ));
        }
    }
    
    // mazani ajaxem
    public function ajaxDelete($request, $response) {
        $vystup = '';
        
        $link_id = $request->getParam('link_id');
        
        // TODO zjistit jestli mame write prava na inherit table a objekt
        
        
        $returned_data = $this->container->stor->delete_stor_file($link_id);
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($returned_data['message']);
        return $response;
    }
    
    // prejmenovani ajaxem. nebudeme na to delat zvlastni funkci ve tride, je to jednoduche
    public function ajaxUpdate($request, $response) {
        $vystup = '';
        
        $link_id = $request->getParam('link_id');
        $new_fname = $request->getParam('new_fname');
        
        // nacteme si link
        $this->container->db->where("c_uid", $link_id);
        $link_data = $this->container->db->getOne('t_stor_links');
        if ($this->container->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $vystup = 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne';
        }
        else {
            // pokud mame prava na tento objekt
            if ($this->container->permissions->have_action_on_object($link_data['c_inherit_table'], $link_data['c_inherit_object'], 'write')) {
                // zmenime nazev na novy
                $data = Array (
                    'c_filename' => $new_fname
                );
                $this->container->db->where("c_uid", $link_id);
                if ($this->container->db->update('t_stor_links', $data)) {
                    $vystup = 'soubor byl prejmenovan';
                }
                else {
                    $vystup = 'prejmenovani se nepovedlo';
                }
            }
            else {
                $vystup = 'k prejmenovani nemate prava';
            }
        }
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($vystup);
        return $response;
    }
    
}
