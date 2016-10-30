<?php
namespace Glued\Controllers;

use Glued\Controllers\Controller; // needed because Auth is in a directory below

class UploadController extends Controller
{

    public function get($request, $response, $args)
    {
        return $this->container->view->render($response, 'up.twig', $args);
    }


    // responds to the signin get request (shows signin form)
    public function post($request, $response)
    {
      $files = $request->getUploadedFiles();
      if (empty($files['files'])) {
          throw new Exception('Expected uploaded files, got none.');
      }

      foreach ($files['files'] as $newfile) {
        if ($newfile->getError() === UPLOAD_ERR_OK) {
            // rewrite with https://gist.github.com/frostbitten/c1dce70023321158a2fd#file-upload-twig
            // and https://github.com/brandonsavage/Upload
            // https://translate.google.cz/translate?hl=cs&sl=zh-CN&tl=en&u=http%3A%2F%2Fwww.php-frameworks.org%2Fforum.php%3Fmod%3Dviewthread%26tid%3D5%26page%3D1%26authorid%3D1&sandbox=1
            // then look at using vue in slim https://github.com/pklink/machdas
            $f[]['orig_name'] = $newfile->getClientFilename();
            $f[]['size'] = $newfile->getClientFilename();
            $f[]['mime'] = $newfile->getClientFilename();
            $f[]['result'] = $newfile->getClientFilename();
            $newfile->moveTo("/var/www/html/glued/private/stor/".$newfile->getClientFilename());
        } else {
            $this->container->flash->addMessage('error', 'Some or all of your files failed to upload.');
            return $response->withRedirect($this->container->router->pathFor('upload'));
        }
     }

     $success = implode(', ', array_map(function ($entry) {
        return $entry['orig_name'];
     }, $f));
 
    $this->container->flash->addMessage('info', 'All your files (' . $success . ') were successfully uploaded.');
    return $response->withRedirect($this->container->router->pathFor('upload'));


    }




}
