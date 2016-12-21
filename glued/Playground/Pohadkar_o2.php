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
        $parent_path = '/var/www/html/glued/private/stor/o2';
        if ($dh = opendir($parent_path)) {
            while( false !== ($file = readdir($dh))) {
                if ($file == '.' or $file == '..') { continue; }
                if (is_file($parent_path.'/'.$file)) { $array_of_filenames[] = $file; }
            }
            closedir($dh);
        }
        if (count($array_of_filenames) == 0) {
            $vystup .= '<p>v adresari O2 nejsou zadne soubory</p>';
        }
        else {
            $vystup .= '<ul>';
            foreach ($array_of_filenames as $filename) {
                $vystup .= '<li>'.$filename.'</li>';
            }
            $vystup .= '</ul>';
        }
        
        
        
        
        return $this->container->view->render($response, 'o2.twig', array('vystup' => $vystup));
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
            $f[]['orig_name'] = $newfile->getClientFilename();
            /*
            $f[]['size'] = $newfile->getClientFilename();
            $f[]['mime'] = $newfile->getClientFilename();
            $f[]['result'] = $newfile->getClientFilename();
            */
            $newfile->moveTo("/var/www/html/glued/private/stor/o2/".$newfile->getClientFilename());
        } else {
            $this->container->flash->addMessage('error', 'Some or all of your files failed to upload.');
            return $response->withRedirect($this->container->router->pathFor('o2gui'));
        }
     }

     $success = implode(', ', array_map(function ($entry) {
        return $entry['orig_name'];
     }, $f));
 
    $this->container->flash->addMessage('info', 'All your files (' . $success . ') were successfully uploaded.');
    return $response->withRedirect($this->container->router->pathFor('o2gui'));
    }
    
    
}
