<?php

namespace Glued\Playground;
use Glued\Controllers\Controller;

class Pohadkar_o2 extends Controller
{
    
    // fukce co vypise prehled nahranych a formular pro nahrani dalsiho
    
    public function uploadgui($request, $response)
    {
        $vystup = '';
        // prehled nahranych zipu
        $array_of_filenames = array();
        $parent_path = '/var/www/html/glued/private/import/O2cz';
        if ($dh = opendir($parent_path)) {
            while( false !== ($file = readdir($dh))) {
                if ($file == '.' or $file == '..') { continue; }
                if (is_dir($parent_path.'/'.$file)) { $array_of_filenames[] = $file; }
            }
            closedir($dh);
        }
        if (count($array_of_filenames) == 0) {
            $vystup .= '<p>v adresari O2 nejsou zadne soubory</p>';
        }
        else {
            $vystup .= '<ul>';
            foreach ($array_of_filenames as $filename) {
                $vystup .= '<li>'.$filename.' <a href="faktura/'.$filename.'">analyzuj</a></li>';
            }
            $vystup .= '</ul>';
        }
        
        
        
        
        return $this->container->view->render($response, 'o2.twig', array('vystup' => $vystup));
    }
    
    // funkce ktera vypise analyzu jednoho diru
    public function analyzadiru($request, $response, $args)
    {
        $vystup = '';
        // prehled souboru v diru
        $array_of_filenames = array();
        $array_of_xml = array();
        $parent_path = '/var/www/html/glued/private/import/O2cz/'.$args['dirname'];
        if ($dh = opendir($parent_path)) {
            while( false !== ($file = readdir($dh))) {
                if ($file == '.' or $file == '..') { continue; }
                if (is_file($parent_path.'/'.$file)) {
                    $array_of_filenames[] = $file;
                    if (preg_match('|\.xml$|', $file)) {
                        $array_of_xml[] = $file;
                    }
                }
            }
            closedir($dh);
        }
        
        // vypiseme vsechny soubory
        if (count($array_of_filenames) == 0) {
            $vystup .= '<p>v adresari nejsou zadne soubory</p>';
        }
        else {
            $vystup .= '<ul>';
            foreach ($array_of_filenames as $filename) {
                $vystup .= '<li>'.$filename.'</li>';
            }
            $vystup .= '</ul>';
        }
        
        // analyzujem jednotlive soubory
        if (count($array_of_xml) == 0) {
            $vystup .= '<p>v adresari nejsou zadne xml</p>';
        }
        else {
            foreach ($array_of_xml as $xmlfilename) {
                
                $obsah = file_get_contents($parent_path.'/'.$xmlfilename);
                
                $p = xml_parser_create();
                xml_parse_into_struct($p, $obsah, $vals);
                xml_parser_free($p);
                
                $vystup .= '<h3>soubor '.$xmlfilename.' prevedeny na pole</h3>';
                
                foreach ($vals as $tag_data) {
                    if ($tag_data['type'] == 'open' or $tag_data['type'] == 'complete') {
                        $vystup .= '<div style="margin-left: '.($tag_data['level'] * 20).'px;">';
                        $vystup .= '<span style="color: grey;">'.$tag_data['tag'].'</span> ';
                        if (isset($tag_data['attributes'])) { $vystup .= ' <span style="color: grey;" title="atributy: '.print_r($tag_data['attributes'], true).'">[atr]</span> '; }
                        if (isset($tag_data['value'])) { $vystup .= ' : <span style="color: black; font-weight: bold;">'.$tag_data['value'].'</span> '; }
                        
                        $vystup .= '</div>';
                    }
                }
            }
        }
        
        
        return $this->container->view->render($response, 'o2-analyza.twig', array('vystup' => $vystup, 'zipname' => $args['dirname']));
    }
    
    
    // funkce co zpracuje poslany nahravany zip a nahraje ho do stor/o2
    public function savezip($request, $response)
    {
        $files = $request->getUploadedFiles();
        if (empty($files['files'])) {
            throw new Exception('Expected uploaded files, got none.');
        }
        
        foreach ($files['files'] as $newfile) {
            if ($newfile->getError() === UPLOAD_ERR_OK) {
                $new_file = array();
                $new_file['orig_name'] = $newfile->getClientFilename();
                $new_file['mime'] = $newfile->getClientMediaType();
                $new_file['size'] = $newfile->getSize();
                
                if ($new_file['mime'] == 'application/zip') {
                    
                    $f[] = $new_file;
                    /*
                    $f[]['result'] = $newfile->getClientFilename();
                    */
                    
                    $full_path = "/var/www/html/glued/private/import/O2cz/";
                    $casti = explode('.', $new_file['orig_name']);
                    array_pop($casti);
                    $posible_dir_name = implode('.', $casti);
                    
                    $newfile->moveTo($full_path.$new_file['orig_name']);
                    
                    // rozbalime
                    $zip = new \ZipArchive;
                    $res = $zip->open($full_path.$new_file['orig_name']);
                    if ($res === TRUE) {
                        $zip->extractTo($full_path.$posible_dir_name);
                        $zip->close();
                    }
                    else {
                        $this->container->flash->addMessage('error', 'file: '.$new_file['orig_name'].' is not valid zip');
                        return $response->withRedirect($this->container->router->pathFor('o2gui'));
                    }
                }
                else {
                    $this->container->flash->addMessage('error', 'file: '.$new_file['orig_name'].' is not zip');
                    return $response->withRedirect($this->container->router->pathFor('o2gui'));
                }
            } else {
                $this->container->flash->addMessage('error', 'Some or all of your files failed to upload.');
                return $response->withRedirect($this->container->router->pathFor('o2gui'));
            }
        }
        
        $success = implode(', ', array_map(function ($entry) {
        return $entry['orig_name'].'('.$entry['mime'].', '.$entry['size'].')';
        }, $f));
        
        $this->container->flash->addMessage('info', 'All your files (' . $success . ') were successfully uploaded.');
        return $response->withRedirect($this->container->router->pathFor('o2gui'));
    }
    
    
}
