<?php

use \Glued\Middleware\Auth\AuthMiddleware;
use \Glued\Middleware\Auth\GuestMiddleware;
use Jsv4\Validator as jsonv;



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



// group of routes where user has to be signed in, plus global csrf check
$app->group('', function () {

  // $app isn't in scope inside here, we use $this instead
  // we could use $app only if we'd have to call "function () use ($app)"
  $this->get('/auth/settings', 'AuthController:getSettings')->setName('auth.settings');
  $this->post('/auth/password/change', 'AuthController:postChangePassword')->setName('auth.password.change');
  $this->post('/auth/identification/change', 'AuthController:postChangeIdentification')->setName('auth.identification.change');
  $this->get('/auth/signout', 'AuthController:getSignOut')->setName('auth.signout');
  $this->get('/upload', 'UploadController:get')->setName('upload');
  $this->post('/upload', 'UploadController:post')->setName('upload');
  
  $this->get('/acl/crossroad', 'AclController:getAclCrossroad')->setName('acl.crossroad');
  $this->post('/acl/crossroad', 'AclController:postAddAction');
  $this->get('/acl/usergroups/{id}', 'AclController:getUserGroups');
  $this->post('/acl/usergroups', 'AclController:postUserGroups')->setName('acl.update.membership');
  $this->get('/acl/userunix/{id}', 'AclController:getUserUnix');
  $this->post('/acl/userunix', 'AclController:postUserUnix')->setName('acl.update.userunix');
  $this->get('/acl/userprivileges/{id}', 'AclController:getUserPrivileges');    // privilgie uzivatele a form na pridani noveho
  $this->get('/acl/groupprivileges/{id}', 'AclController:getGroupPrivileges');  // privilegia skupiny a form na pridani noveho
  $this->get('/acl/roleprivileges', 'AclController:getRolePrivileges')->setName('acl.roleprivileges');  // privilegia dalsich roli a form na pridani noveho
  $this->get('/acl/tableprivileges/{tablename}', 'AclController:getTableTablePrivileges');  // table privilegia na tabulku a form na pridani noveho
  $this->get('/acl/globalprivileges/{tablename}', 'AclController:getGlobalTablePrivileges');  // global privilegia na tabulku a form na pridani noveho
  $this->post('/acl/newprivilege', 'AclController:postNewPrivilege')->setName('acl.new.privilege'); // pridava privilegium ruznych typu z ruznych stranek
  
  // stor
  $this->get('/stor/uploader[/~/{dir}]', 'StorController:storUploadGui')->setName('stor.uploader');
  $this->post('/stor/uploader', 'StorController:uploaderSave');
  
  // GUI for assets, consumables a pod
  $this->get('/assets', 'StockController:stockGui')->setName('assets.gui');
  $this->get('/consumables', 'ConsumablesController:consumablesGui')->setName('consumables.gui');
  $this->get('/parts', 'PartsController:gui')->setName('parts.gui');
  
})->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);


// another group of routes, where user have to be signed in, but no csrf check. typical - api (ajax) scripts
// or pages with js generated forms
$app->group('', function () {
  
  // strankove veci (vraci html)
  $this->get('/accounting/costs', 'AccountingCostsController:getCosts')->setName('accounting.costs');
  $this->get('/accounting/costs/new', 'AccountingCostsController:addCostForm')->setName('accounting.addcostform');
  $this->get('/accounting/costs/[{id}]', 'AccountingCostsController:editCostForm')->setName('accounting.editcostform');
  
  // generovane formulare pro assets, cosumables a pod
  $this->get('/assets/new', 'StockController:addStockForm')->setName('assets.addform');
  $this->get('/assets/quicknew', 'StockController:addQuickForm')->setName('assets.addquickform');
  $this->get('/assets/edit/{id:[0-9]+}', 'StockController:editStockForm')->setName('assets.editform');
  $this->get('/consumables/new', 'ConsumablesController:addStockForm')->setName('consumables.addform');
  $this->get('/consumables/quicknew', 'ConsumablesController:addQuickForm')->setName('consumables.addquickform');
  $this->get('/consumables/edit/{id:[0-9]+}', 'ConsumablesController:editStockForm')->setName('consumables.editform');
  $this->get('/parts/new', 'PartsController:addForm')->setName('parts.addform');
  $this->get('/parts/quicknew', 'PartsController:addQuickForm')->setName('parts.addquickform');
  $this->get('/parts/edit/{id:[0-9]+}', 'PartsController:editForm')->setName('parts.editform');
  
  // testovaci mazaci stor, bez csrf, protoze form se pise v controleru a ne ve twigu
  $this->post('/stor/uploader/delete', 'StorController:uploaderDelete')->setName('stor.uploader.delete');
  
  // api veci (vraci json)
  $this->post('/api/v1/accounting/costs', 'AccountingCostsControllerApiV1:insertCostApi')->setName('accounting.api.new');
  $this->put('/api/v1/accounting/costs/[{id}]', 'AccountingCostsControllerApiV1:editCostApi')->setName('accounting.api.edit');
  $this->delete('/api/v1/accounting/costs/[{id}]', 'AccountingCostsControllerApiV1:deleteCostApi')->setName('accounting.api.delete');
  
  // api k ukladani formularu pro assets, consumables a pod
  $this->post('/api/v1/assets', 'StockControllerApiV1:insertStockApi')->setName('assets.api.new');
  $this->put('/api/v1/assets/{id:[0-9]+}', 'StockControllerApiV1:editStockApi')->setName('assets.api.edit');
  $this->post('/api/v1/consumables', 'ConsumablesControllerApiV1:insertStockApi')->setName('consumables.api.new');
  $this->put('/api/v1/consumables/{id:[0-9]+}', 'ConsumablesControllerApiV1:editStockApi')->setName('consumables.api.edit');
  $this->post('/api/v1/parts', 'PartsControllerApiV1:insertApi')->setName('parts.api.new');
  $this->put('/api/v1/parts/{id:[0-9]+}', 'PartsControllerApiV1:editApi')->setName('parts.api.edit');
  
  // upload captured foto z modulu assets, consumables a pod, jako uploaded file, i z formu jako normalni soubor
  $this->post('/assets/upload/{id:[0-9]+}[/{name}]', 'StockController:uploaderSave')->setName('assets.upload');
  $this->post('/consumables/upload/{id:[0-9]+}[/{name}]', 'ConsumablesController:uploaderSave')->setName('consumables.upload');
  $this->post('/parts/upload/{id:[0-9]+}[/{name}]', 'PartsController:uploaderSave')->setName('parts.upload');
  
  // api acl
  $this->delete('/api/v1/acl/privileges/[{id}]', 'AclControllerApiV1:deletePrivilegeApi')->setName('acl.api.privilege.delete');
  
  // show stor file (or force download)
  $this->get('/stor/get/{id:[0-9]+}[/{filename}]', 'StorController:serveFile')->setName('stor.serve.file');
  
  // ajax ktery vypise soubory v adresari, protoze vypisujeme, dame tam get metodu
  $this->get('/api/v1/stor/files', 'StorControllerApiV1:showFiles')->setName('stor.api.files');
  
  // barcode
  $this->get('/app/barcode/get-parametry', 'BarcodeController:barCode')->setName('barcode.code');
  
})->add(new AuthMiddleware($container));


// group of routes where user must not be signed in to see them
$app->group('', function () {

  $this->get('/auth/signup', 'AuthController:getSignUp')->setName('auth.signup');
  $this->post('/auth/signup', 'AuthController:postSignUp'); // we only need to set the name once for an uri, hence here not a setName again
  $this->get('/auth/signin', 'AuthController:getSignIn')->setName('auth.signin');
  $this->post('/auth/signin', 'AuthController:postSignIn'); // we only need to set the name once for an uri, hence here not a setName again

})->add(new GuestMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);

// APIs

$app->group('', function () {
  $this->get('/api/0.1/test[/{id}]', '\Glued\Controllers\Api\v0_1\TestController::get');
  $this->get('/jsonvtest', function ($request, $response) {
    return jsonv::isValid([ 'a' => 'b' ], []);
  });

  // timepixels
  $this->get('/api/0.1/timepixels[/{id}]', 'TimeController:get');
  $this->put('/api/0.1/timepixels[/{id}]', '\Glued\Controllers\Api\v0_1\TimePixelsController::put');
  $this->post('/api/0.1/timepixels[/]', 'TimeController:post');
  $this->delete('/api/0.1/timepixels[/{id}]', 'TimeController:delete');
});


// doesnt matter if signed or not
$app->group('', function () {
    
    $this->get('/development/tools', 'HomeController:showTools')->setName('development.tools');
    
});


// PLAYGROUND

// Pohadkar_Jsv4 (proc tu jsou ty :: pozor na to, rozdil oproti : , ktere je vsude jinde. odted davam vsude jen jednu dvojtecku)
$app->get('/playground/pohadkar_jsv4/validationtest', '\Glued\Playground\Pohadkar_Jsv4:validationtest');
$app->get('/playground/pohadkar_jsv4/schematest', '\Glued\Playground\Pohadkar_Jsv4:schematest');
$app->get('/playground/pohadkar_jsv4/schematest2', '\Glued\Playground\Pohadkar_Jsv4:schematest2');

// pohadkar upload a prehled zipu (POZOR, funkci volam s jednou dvojteckou : aby tam bylo this)
$app->get('/playground/pohadkar_o2/gui', '\Glued\Playground\Pohadkar_o2:uploadgui')->setName('o2gui')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
$app->post('/playground/pohadkar_o2/gui', '\Glued\Playground\Pohadkar_o2:savezip')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
$app->get('/playground/pohadkar_o2/faktura[/{dirname}]', '\Glued\Playground\Pohadkar_o2:analyzadiru');

// pohadkar, zadavani plateb a generovani prikazu bance
$app->get('/playground/pohadkar_platby/list', '\Glued\Playground\Pohadkar_platby:list')->setName('platbylist');
$app->get('/playground/pohadkar_platby/new', '\Glued\Playground\Pohadkar_platby:form')->setName('platbynew')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
$app->post('/playground/pohadkar_platby/new', '\Glued\Playground\Pohadkar_platby:insert')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
$app->get('/playground/pohadkar_platby/prikaz[/{id}]', '\Glued\Playground\Pohadkar_platby:prikaz');

// pohadkar, moje testy
$app->get('/playground/pohadkar_testy/innodb', '\Glued\Playground\Pohadkar_testy:form')->setName('innodb')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
$app->post('/playground/pohadkar_testy/innodb', '\Glued\Playground\Pohadkar_testy:test')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);



// Killua_Jsv4
$app->get('/playground/killua_jsv4/validationtest', '\Glued\Playground\Killua_Jsv4:validationtest');
$app->get('/playground/killua_jsv4/schematest', '\Glued\Playground\Killua_Jsv4:schematest');
$app->get('/playground/killua_jsv4/schematest2', '\Glued\Playground\Killua_Jsv4:schematest2');
$app->get('/playground/killua_db/list1', '\Glued\Playground\Killua_db:list1');
$app->get('/playground/killua_jsfb/moz', '\Glued\Playground\Killua_JsonSchemaForm:json_moz');



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