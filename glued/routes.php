<?php

use \Glued\Middleware\Auth\AuthMiddleware;
use \Glued\Middleware\Auth\GuestMiddleware;

// TODO use nesting of groups to split api/nonapi routes

/*
 * The home route [/]
*/


$app->group('', function () {
  $this->get('/', function ($request, $response) {
     $this->logger->info("Slim-Skeleton '/' route");   // Sample log message
     return 'A basic route returning a string and writing a log entry about it. Look at<br />
     - <a href="home">here</a> a propper home controller. DI loaded, extending a common Controller class<br />
     ';
  });

  $this->get('/home', 'HomeController:index')->setName('home');
})->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);



// group of routes where user has to be signed in
$app->group('', function () {

  // $app isn't in scope inside here, we use $this instead
  // we could use $app only if we'd have to call "function () use ($app)"
  $this->get ('/auth/password/change', 'AuthController:getChangePassword')->setName('auth.password.change');
  $this->post('/auth/password/change', 'AuthController:postChangePassword'); // we only need to set the name once for an uri, hence here not a setName again
  $this->get ('/auth/signout', 'AuthController:getSignOut')->setName('auth.signout');
  $this->get ('/upload', 'UploadController:get')->setName('upload');
  $this->post('/upload', 'UploadController:post')->setName('upload');

})->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);


// group of routes where user must not be signed in to see them
$app->group('', function () {

  $this->get('/auth/signup', 'AuthController:getSignUp')->setName('auth.signup');
  $this->post('/auth/signup', 'AuthController:postSignUp'); // we only need to set the name once for an uri, hence here not a setName again
  $this->get('/auth/signin', 'AuthController:getSignIn')->setName('auth.signin');
  $this->post('/auth/signin', 'AuthController:postSignIn'); // we only need to set the name once for an uri, hence here not a setName again

})->add(new GuestMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);

// APIs

$app->group('', function () {
  use Jsv4\Validator as jsonv;
  $this->get('/api/0.1/test[/{id}]', '\Glued\Controllers\Api\v0_1\TestController::get');
  $this->get('/jsonvtest', function ($request, $response) {
    return jsonv::isValid([ 'a' => 'b' ], []);
  });

  // timepixels
  $this->get('/api/0.1/timepixels[/{id}]', 'TimeController:get');
  $this->put('/api/0.1/timepixels[/{id}]', '\Glued\Controllers\Api\v0_1\TimePixelsController::put');
  $this->post('/api/0.1/timepixels[/]', 'TimeController:post');
  //$app->delete('/api/0.1/timepixels[/{id}]', '\Glued\Controllers\Api\v0_1\TimePixelsController::delete');
  $app->delete('/api/0.1/timepixels[/{id}]', 'TimeController:delete');
});

/*
// test
$app->get('/api/0.1/test[/{id}]', '\Glued\Controllers\Api\v0_1\TestController::get');
$app->get('/jsonvtest', function ($request, $response) {
  use Jsv4\Validator as jsonv;
  return jsonv::isValid([ 'a' => 'b' ], []);
});


// timepixels
$app->get('/api/0.1/timepixels[/{id}]', 'TimeController:get');
$app->put('/api/0.1/timepixels[/{id}]', '\Glued\Controllers\Api\v0_1\TimePixelsController::put');
$app->post('/api/0.1/timepixels[/]', 'TimeController:post');

//$app->delete('/api/0.1/timepixels[/{id}]', '\Glued\Controllers\Api\v0_1\TimePixelsController::delete');
$app->delete('/api/0.1/timepixels[/{id}]', 'TimeController:delete');
*/


/**

Glued's APIs are constructed closely to the concepts introduced
by Phlil Sturgeon's book "Build APIs you won't hate". The short
summary is:

- Always carry around the API version in the URL
- Always use named timezones, not numerical offsets
- Never have verbs (actions) in the URL, so:
  NOPE: POST /users/5/send-message
  YEAH: PATCH /users/philsturgeon/messages/xdWRwerG
  YEAH: POST /messages

  Content-Type: application/json
  {
   [{
     "user" : { "id" : 10 }
     "message" : "Hello!"
    },
    {
     "user" : { "username" : "philsturgeon" }
     "message" : "Hello!"
    }]
  }

- Each resource has its own controller
- Never do any routing magic, write out every method to every route.
- Use namespaced responses (see I/O theory, pg. 24)
- Identify error messages by (constant) numerical codes, not (possibly changing) strings
- Use embedded documents
- Use pagination to limit response size, watch out caching issues
- Do HATEOAS (multiple "response views" & links)

**/