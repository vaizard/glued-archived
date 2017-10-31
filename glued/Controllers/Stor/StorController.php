<?php

namespace Glued\Controllers\Stor;
use Glued\Controllers\Controller;
use Glued\Classes\Stor;

class StorController extends Controller
{
    
    // fukce co vypise prehled nahranych a formular pro nahrani dalsiho
    
    public function storUploadGui($request, $response)
    {
        $vystup = '';
        
        // prehled nahranych souborů pro modul stor
        $sloupce = array("lin.c_uid", "lin.c_filename", "obj.sha512", "obj.doc->>'$.data.size' as size", "obj.doc->>'$.data.mime' as mime");
        $this->container->db->join("stor_objects obj", "obj.sha512=lin.c_sha512", "LEFT");
        $this->container->db->where("c_path", 'stor/p');
        $files = $this->container->db->get('t_stor_links lin', null, $sloupce);
        if (count($files) > 0) {
            $vystup .= '<div class="card items">';
            $vystup .= '<ul class="item-list striped">';
            $vystup .= '
                            <li class="item item-list-header">
                                <div class="item-row">
                                    <div class="item-col item-col-header fixed item-col-img md">
                                        <div>
                                            <span>Media</span>
                                        </div>
                                    </div>
                                    <div class="item-col item-col-header item-col-title">
                                        <div>
                                            <span>Name</span>
                                        </div>
                                    </div>
                                    <div class="item-col item-col-header item-col-sales">
                                        <div>
                                            <span>Size</span>
                                        </div>
                                    </div>
                                    <div class="item-col item-col-header item-col-category">
                                        <div class="no-overflow">
                                            <span>Category</span>
                                        </div>
                                    </div>
                                    <div class="item-col item-col-header item-col-author">
                                        <div class="no-overflow">
                                            <span>Owner</span>
                                        </div>
                                    </div>
                                    <div class="item-col item-col-header item-col-date">
                                        <div>
                                            <span>Published</span>
                                        </div>
                                    </div>
                                    <div class="item-col item-col-header fixed item-col-actions-dropdown"> </div>
                                </div>
                            </li>
            ';
            foreach ($files as $data) {
                $vystup .= '
                            <li class="item">
                                <div class="item-row">
                                    <div class="item-col fixed item-col-img md">
                                        <a href="item-editor.html">
                                            <div class="item-img rounded" style="background-image: url(https://s3.amazonaws.com/uifaces/faces/twitter/brad_frost/128.jpg)"></div>
                                        </a>
                                    </div>
                                    <div class="item-col fixed pull-left item-col-title">
                                        <div class="item-heading">Name</div>
                                        <div>
                                            <a href="item-editor.html" class="">
                                                <h4 class="item-title"> '.$data['c_filename'].' </h4>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="item-col item-col-sales">
                                        <div class="item-heading">Sales</div>
                                        <div> '.$data['size'].' B </div>
                                    </div>
                                    <div class="item-col item-col-category no-overflow">
                                        <div class="item-heading">Category</div>
                                        <div class="no-overflow">
                                            <a href="">Stor</a>
                                        </div>
                                    </div>
                                    <div class="item-col item-col-author">
                                        <div class="item-heading">Author</div>
                                        <div class="no-overflow">
                                            <a href="">Meadow Katheryne</a>
                                        </div>
                                    </div>
                                    <div class="item-col item-col-date">
                                        <div class="item-heading">Published</div>
                                        <div class="no-overflow"> 21 SEP 10:45 </div>
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
                /*
                $vystup .= '
                <li class="item">
                    <div class="item-row">
                        <div class="item-col fixed item-col-img md">
                            <a href="item-editor.html">
                                <div class="item-img rounded" style="background-image: url(https://s3.amazonaws.com/uifaces/faces/twitter/brad_frost/128.jpg)"></div>
                            </a>
                        </div>
                        <div class="item-col fixed pull-left item-col-title">
                            <div class="item-heading">Name</div>
                            <div>
                                <a href="item-editor.html" class="">
                                    <h4 class="item-title"> '.$data['c_filename'].' </h4>
                                </a>
                            </div>
                        </div>
                        <div class="item-col item-col-sales">
                            <div class="item-heading">Sales</div>
                            <div> '.$data['size'].' byte </div>
                        </div>
                        <div class="item-col item-col-category no-overflow">
                            <div class="item-heading">Category</div>
                            <div class="no-overflow">
                                <a href="">'.$data['mime'].'</a>
                            </div>
                        </div>
                        <div class="item-col item-col-date">
                            <div class="item-heading">Published</div>
                            <div class="no-overflow"> 21 SEP 10:45 </div>
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
                                            <a class="remove" href="#" data-toggle="modal" data-target="#confirm-modal">
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
                </li>';
                */
                // 
            }
            $vystup .= '</ul>';
            $vystup .= '</div>';
        }
        else {
            $vystup .= '<div class="card card-block"><p>v modulu stor nejsou zadne public soubory</p></div>';
        }
        
        // test json dotazu
        /*
        $vystup .= '<h3>vypis vsech obrazku vetsich nez 20 kb</h3>';
        $sloupce = array("sha512", "doc->>'$.data.size' as size", "doc->>'$.data.mime' as mime");
        $this->container->db->where("doc->>'$.data.size'", 20000, ">");
        $files = $this->container->db->get('stor_objects', null, $sloupce);
        if (count($files) > 0) {
            $vystup .= '<ul>';
            foreach ($files as $data) {
                $vystup .= '<li>'.$data['sha512'].' ('.$data['size'].' kb, '.$data['mime'].')</li>';
            }
            $vystup .= '</ul>';
        }
        else {
            $vystup .= '<p>v modulu stor nejsou zadne public soubory</p>';
        }
        */
        
        return $this->container->view->render($response, 'stor-upload-gui.twig', array('vystup' => $vystup, 'article_class' => 'items-list-page'));
    }
    
    
    // funkce co zpracuje poslany nahravany soubor
    public function uploaderSave($request, $response)
    {
        $files = $request->getUploadedFiles();
        if (empty($files['file'])) {
            throw new Exception('Expected uploaded file, got none.');
        }
        
        $newfile = $files['file'];
        
        if ($newfile->getError() === UPLOAD_ERR_OK) {
            $filename = $newfile->getClientFilename();
            $sha512 = hash_file('sha512', $_FILES['file']['tmp_name']);
            
            // zjistime jestli soubor se stejnym hashem uz mame
            $this->container->db->where("sha512", $sha512);
            $this->container->db->getOne('stor_objects');
            if ($this->container->db->count == 0) {
                
                // vytvorime tomu adresar
                $dir1 = substr($sha512, 0, 1);
                $dir2 = substr($sha512, 1, 1);
                $dir3 = substr($sha512, 2, 1);
                $dir4 = substr($sha512, 3, 1);
                
                $cilovy_dir = '../private/stor/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.$dir4;
                
                if (!is_dir($cilovy_dir)) { mkdir($cilovy_dir, 0777, true); }
                
                // presuneme
                // $full_path = "/var/www/html/glued/private/";
                $newfile->moveTo($cilovy_dir.'/'.$sha512);
                
                // pokud ne, vlozime
                $new_file_array = array();
                $new_file_array['_v'] = '1';
                $new_file_array['sha512'] = $sha512;
                $new_file_array['size'] = $newfile->getSize();
                $new_file_array['mime'] = $newfile->getClientMediaType();
                $new_file_array['checked'] = false;
                $new_file_array['ts_created'] = time();
                $new_file_array['storage'] = array(array("driver" => "fs", "path" => $cilovy_dir));
                
                $new_data_array = array();
                $new_data_array['data'] = $new_file_array;
                
                $json_string = json_encode($new_data_array);
                
                // pozor, spojit dve vkladani pres commit, TODO
                
                // vlozime do objects
                $data = Array ("doc" => $json_string);
                $this->container->db->insert ('stor_objects', $data);
                
                // vlozime do links
                $data = Array (
                "c_sha512" => $sha512,
                "c_path" => "stor/p",
                "c_filename" => $filename
                );
                $this->container->db->insert ('t_stor_links', $data);
                
                $this->container->flash->addMessage('info', 'Your file ('.$filename.') was uploaded successfully.');
            }
            else {
                // soubor uz existuje v objects ale vlozime ho aspon do links
                $data = Array (
                "c_sha512" => $sha512,
                "c_path" => "stor/p",
                "c_filename" => $filename
                );
                $this->container->db->insert ('t_stor_links', $data);
                
                $this->container->flash->addMessage('info', 'Your file ('.$filename.') was uploaded successfully as link. Its hash already exists in objects table.');
            }
        }
        else {
            $this->container->flash->addMessage('error', 'your file failed to upload.');
        }
        
        return $response->withRedirect($this->container->router->pathFor('stor.uploader'));
    }
    
    // funkce pro post test smazani linku (a pokud je posledni tak i objektu)
    public function uploaderDelete($request, $response)
    {
        $link_id = (int) $request->getParam('file_uid');
        
        // nacteme si link a jeho sha512
        $this->container->db->where("c_uid", $link_id);
        $link_data = $this->container->db->getOne('t_stor_links');
        if ($this->container->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $this->container->flash->addMessage('error', 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne');
        }
        else {
            $hash = $link_data['c_sha512'];
            
            // spocitame kolik mame linku s timto hasem
            $this->container->db->where("c_sha512", $hash);
            $links = $this->container->db->get('t_stor_links');
            
            //pokud mame jen jeden, smazeme i objekt
            if (count($links) == 1) {
                // nejdriv smazem z links
                $this->container->db->where("c_uid", $link_id);
                if ($this->container->db->delete('t_stor_links')) {
                    // nacteme si z object cestu ke smazani souboru, i kdz, sla by odvodit, ale muze tam byt prave jiny driver a pak cesta neni dana hashem, TODO
                    // zatim predpokladame driver fs, [0] znamena prvni prvek pole storage, coz je objekt takze za tim zase zaciname teckou
                    // rawQuery v joshcam vraci vzdy pole, i kdyz je vysledek jen jeden
                    $objects = $this->container->db->rawQuery(" SELECT `doc`->>'$.data.storage[0].path' AS path FROM stor_objects WHERE sha512 = ? ", Array ($hash));
                    // TODO, kontrola jestli je jeden vysledek a jestli neni path prazdna
                    $file_to_delete = $objects[0]['path'].'/'.$hash;
                    unlink($file_to_delete);
                    // mazani z objects
                    $this->container->db->where("sha512", $hash);
                    if ($this->container->db->delete('stor_objects')) {
                        $this->container->flash->addMessage('info', 'soubor '.$file_to_delete.' byl komplet smazan z links i object.');
                    }
                    else {
                        $this->container->flash->addMessage('info', 'soubor '.$file_to_delete.' byl smazan z links, ale zrejme nejakou systemovou chybou zustal v objects a neodkazuje ted na nej zadny link.');
                    }
                }
                else {
                    $this->container->flash->addMessage('error', 'smazani se nepovedlo');
                }
            }
            else if (count($links) > 1) {
                $this->container->db->where("c_uid", $link_id);
                if ($this->container->db->delete('t_stor_links')) {
                    $this->container->flash->addMessage('info', 'link na soubor byl smazan, ale bylo jich vic, takze soubor zustava');
                }
                else {
                    $this->container->flash->addMessage('error', 'smazani se nepovedlo');
                }
            }
            else {
                $this->container->flash->addMessage('error', 'hash souboru neexistuje, zahadna chyba');
            }
        }
        
        return $response->withRedirect($this->container->router->pathFor('stor.uploader'));
    }
    
}
