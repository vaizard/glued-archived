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
  $app->get('/upload', function ($request, $response, $args) {
      return $this->view->render($response, 'up.twig', $args);
  })->setName('upload');
  $app->post('/upload', function ($request, $response, $args) {
      $files = $request->getUploadedFiles();
      if (empty($files['newfile'])) {
          throw new Exception('Expected a newfile');
      }
      $newfile = $files['newfile'];
      // do something with $newfile
      if ($newfile->getError() === UPLOAD_ERR_OK) {
          $uploadFileName = $newfile->getClientFilename();
          $newfile->moveTo("/var/www/html/glued/logs/$uploadFileName");
      }
  });


})->add(new AuthMiddleware($container));


// group of routes where user must not be signed in to see them
$app->group('', function () {

  $this->get('/auth/signup', 'AuthController:getSignUp')->setName('auth.signup');
  $this->post('/auth/signup', 'AuthController:postSignUp'); // we only need to set the name once for an uri, hence here not a setName again
  $this->get('/auth/signin', 'AuthController:getSignIn')->setName('auth.signin');
  $this->post('/auth/signin', 'AuthController:postSignIn'); // we only need to set the name once for an uri, hence here not a setName again

})->add(new GuestMiddleware($container));
