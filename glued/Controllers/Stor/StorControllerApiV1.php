<?php

namespace Glued\Controllers\Stor;
use Glued\Controllers\Controller;
use Glued\Classes\Stor;
use Glued\Classes\Auth;

class StorControllerApiV1 extends Controller
{
    
    // fukce co vypise prehled souboru v adresari
    public function showFiles($request, $response)
    {
        $vystup = '';
        
        $dirname = $request->getParam('dirname');
        
        // umisteni
        $vystup .= '<div class="card">';
        if (empty($dirname)) { $vystup .= '<div class="card-block">Nacházíte se v rootu</div>'; }
        else { $vystup .= '<div class="card-block">Nacházíte se v adresáři <strong>'.$dirname.'</strong></div>'; }
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
        // kdyz je prazdny, vypiseme app diry
        if (empty($dirname)) {
            foreach ($this->container->stor->app_dirs as $dir => $description) {
                $vystup .= '
                            <li class="item">
                                <div class="item-row">
                                    <div class="item-col fixed">
                                        <i class="fa fa-folder-o fa-2x"></i>
                                    </div>
                                    <div class="item-col fixed pull-left item-col-title">
                                        <div class="item-heading">Name</div>
                                        <div>
                                            <a href="" onclick="show_files(\''.$dir.'\');return false;" class="">
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
        else {
            // dvojtecka smer nahoru
            $vystup .= '
                        <li class="item">
                            <div class="item-row">
                                <div class="item-col fixed">
                                    <i class="fa fa-folder-open-o fa-2x"></i>
                                </div>
                                <div class="item-col fixed pull-left item-col-title">
                                    <div class="item-heading">Name</div>
                                    <div>
                                        <a href="" onclick="show_files(\'\');return false;" class="">
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
            // kdyz existuje, vypiseme dvojtecku a soubory
            // kdyz neexistuje vypiseme dvojtecku a nejakou chybu
            if (isset($this->container->stor->app_dirs[$dirname])) {
                // prehled nahranych souborů pro modul stor
                $sloupce = array("lin.c_uid", "lin.c_owner", "lin.c_filename", "obj.sha512", "obj.doc->>'$.data.size' as size", "obj.doc->>'$.data.mime' as mime", "obj.doc->>'$.data.ts_created' as ts_created");
                $this->container->db->join("t_stor_objects obj", "obj.sha512=lin.c_sha512", "LEFT");
                $this->container->db->where("c_path", $dirname.'/%', 'like');   // TODO dodat mozna LIKE %
                $files = $this->container->db->get('t_stor_links lin', null, $sloupce);
                if (count($files) > 0) {
                    foreach ($files as $data) {
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
                                                        <h4 class="item-title"> '.$data['c_filename'].' </h4>
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
                                                    <a href="">Stor</a>
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
                                                <div class="no-overflow"> '.date("j.n. Y H:i", $data['ts_created']).' </div>
                                            </div>
                                            <div class="item-col fixed item-col-actions-dropdown">
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
                                                                <a class="edit" href="item-editor.html">
                                                                    <i class="fa fa-pencil"></i>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
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
    
    
}
