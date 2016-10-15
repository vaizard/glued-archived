<?php

use \Glued\Middleware\Auth\AuthMiddleware;
use \Glued\Middleware\Auth\GuestMiddleware;

/*
 * The home route [/]
*/

$app->get('/', function ($request, $response) {
   // Sample log message
   $this->logger->info("Slim-Skeleton '/' route");
   // Html page
   return 'A basic route returning a string and writing a log entry about it. Look at<br />
   - <a href="home">here</a> a propper home controller. DI loaded, extending a common Controller class<br />
   ';
});

$app->get('/home', 'HomeController:index')->setName('home');


// group of routes where user has to be signed in
$app->group('', function () {

  // $app isn't in scope inside here, we use $this instead
  // we could use $app only if we'd have to call "function () use ($app)"
  $this->get ('/auth/password/change', 'AuthController:getChangePassword')->setName('auth.password.change');
  $this->post('/auth/password/change', 'AuthController:postChangePassword'); // we only need to set the name once for an uri, hence here not a setName again
  $this->get ('/auth/signout', 'AuthController:getSignOut')->setName('auth.signout');
  $this->get('/upload', function ($request, $response, $args) {
      return $this->view->render($response, 'up.twig', $args);
  })->setName('upload');


  $this->post('/upload', function ($request, $response, $args) {
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
            $this->flash->addMessage('error', 'Some or all of your files failed to upload.');
            return $response->withRedirect($this->router->pathFor('upload'));
        }
     }

     $success = implode(', ', array_map(function ($entry) {
        return $entry['orig_name'];
     }, $f));
 
    $this->flash->addMessage('info', 'All your files (' . $success . ') were successfully uploaded.');
    return $response->withRedirect($this->router->pathFor('upload'));
  });


})->add(new AuthMiddleware($container));


// group of routes where user must not be signed in to see them
$app->group('', function () {

  $this->get('/auth/signup', 'AuthController:getSignUp')->setName('auth.signup');
  $this->post('/auth/signup', 'AuthController:postSignUp'); // we only need to set the name once for an uri, hence here not a setName again
  $this->get('/auth/signin', 'AuthController:getSignIn')->setName('auth.signin');
  $this->post('/auth/signin', 'AuthController:postSignIn'); // we only need to set the name once for an uri, hence here not a setName again

})->add(new GuestMiddleware($container));
