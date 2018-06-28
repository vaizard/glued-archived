<?php

use \Glued\Middleware\Auth\AuthMiddleware;
use \Glued\Middleware\Auth\GuestMiddleware;
use \Glued\Middleware\Permissions\RootMiddleware;
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
  $this->get('/accounting/costs', 'AccountingCostsController:getCosts')->setName('accounting.costs');
  
  $this->get('/permissions/my', 'PermissionsController:getMyAcl')->setName('acl.my');
  $this->post('/permissions/newprivilege', 'PermissionsController:postNewPrivilege')->setName('acl.new.privilege'); // pridava privilegium ruznych typu z ruznych stranek
  
  // stor
  $this->get('/stor/uploader[/~/{dir}[/{oid:[0-9]+}]]', 'StorController:storUploadGui')->setName('stor.uploader');
  $this->post('/stor/uploader', 'StorController:uploaderSave');
  $this->get('/stor/browser', 'StorController:storBrowserGui')->setName('stor.browser');
  
  // GUI for assets, consumables a pod
  $this->get('/assets', 'StockController:stockGui')->setName('assets.gui');
  $this->get('/consumables', 'ConsumablesController:consumablesGui')->setName('consumables.gui');
  $this->get('/parts', 'PartsController:gui')->setName('parts.gui');
  
  // fbevents
  $this->get('/fbevents/main', 'FBEventsController:fbeventsMain')->setName('fbevents.main');
  $this->get('/fbevents/newpage', 'FBEventsController:addPageForm')->setName('fbevents.addpage');
  $this->post('/fbevents/newpage', 'FBEventsController:addPageAction');
  $this->get('/fbevents/page/{id}', 'FBEventsController:fbeventsPage')->setName('fbevents.page');
  $this->post('/fbevents/page/{id}', 'FBEventsController:fbeventsPageUpdate');
  
})->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);


// group of routes, where user has to be signed in, and has to be in root group, csrf check
$app->group('', function () {
  $this->get('/permissions/crossroad', 'PermissionsController:getAclCrossroad')->setName('acl.crossroad');
  $this->get('/permissions/developer', 'PermissionsController:getAclDeveloper')->setName('acl.developer');
  $this->post('/permissions/developer', 'PermissionsController:postAddActionRole');
  
  $this->get('/permissions/usergroups/{id}', 'PermissionsController:getUserGroups')->setName('acl.membership');
  $this->post('/permissions/usergroups', 'PermissionsController:postUserGroups')->setName('acl.update.membership');
  $this->get('/permissions/userunix/{id}', 'PermissionsController:getUserUnix')->setName('acl.userunix');
  $this->post('/permissions/userunix', 'PermissionsController:postUserUnix')->setName('acl.update.userunix');
  $this->get('/permissions/userprivileges/{id}', 'PermissionsController:getUserPrivileges')->setName('acl.userprivileges');    // privilegia uzivatele a form na pridani noveho
  $this->get('/permissions/groupprivileges/{id}', 'PermissionsController:getGroupPrivileges')->setName('acl.groupprivileges');  // privilegia skupiny a form na pridani noveho
  $this->get('/permissions/roleprivileges', 'PermissionsController:getRolePrivileges')->setName('acl.roleprivileges');  // privilegia dalsich roli a form na pridani noveho
  $this->get('/permissions/implementedactions', 'PermissionsController:getImplementedActions')->setName('acl.implementedactions');  // prirazeni status akce abulka
  $this->get('/permissions/tableprivileges/{tablename}', 'PermissionsController:getTableTablePrivileges')->setName('acl.tableprivileges');  // table privilegia na tabulku a form na pridani noveho
  $this->get('/permissions/globalprivileges/{tablename}', 'PermissionsController:getGlobalTablePrivileges')->setName('acl.globalprivileges');  // global privilegia na tabulku a form na pridani noveho
  
  $this->get('/permissions/editaction/{id}', 'PermissionsController:getEditAction')->setName('acl.editaction');    // stranka s editaci jedne akce
  $this->get('/permissions/editrole/{id}', 'PermissionsController:getEditRole')->setName('acl.editrole');    // stranka s editaci jedne role
  $this->post('/permissions/newimplementedaction', 'PermissionsController:postNewImplementedAction')->setName('acl.new.implemented.action'); // pridava novy zaznam do tabulky implemented_action
  
})->add(new AuthMiddleware($container))->add(new RootMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);


// ajaxy s csrf checkem a pregenerovanim ocekavaneho csrf
$app->group('', function () {
  // ajax, ktery po odeslani filtru vraci soubory odpovidajici vyberu
  // hodila by se get metoda, ale radeji pouziju post, protoze se posila pole vybranych filtru a nejsem si jisty jak to bude v getu fungovat
  $this->get('/api/v1/stor/filter', 'StorControllerApiV1:showFilteredFiles')->setName('stor.api.filtered.files');
})->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);


// another group of routes, where user have to be signed in, but no csrf check.
// typical - api (ajax) scripts
// or pages with js generated forms
$app->group('', function () {
  
  // strankove veci (vraci html)
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
  
  // STOR
  // show stor file (or force download)
  $this->get('/stor/get/{id:[0-9]+}[/{filename}]', 'StorController:serveFile')->setName('stor.serve.file');
  // odeslani mazaciho stor formu, s csrf
  $this->post('/stor/uploader/delete', 'StorController:uploaderDelete')->setName('stor.uploader.delete');
  // smazani souboru ajaxem
  $this->post('/api/v1/stor/delete', 'StorControllerApiV1:ajaxDelete')->setName('stor.ajax.delete');
  // editace nazvu souboru ajaxem
  $this->post('/api/v1/stor/update', 'StorControllerApiV1:ajaxUpdate')->setName('stor.ajax.update');
  // update editace stor file (nazev) TODO nemel by tu byt put, kdyz je to update?
  $this->post('/stor/uploader/update', 'StorController:uploaderUpdate')->setName('stor.uploader.update');
  $this->post('/stor/uploader/copymove', 'StorController:uploaderCopyMove')->setName('stor.uploader.copy.move');
  // ajax ktery vypise soubory v adresari, protoze vypisujeme, dame tam get metodu
  $this->get('/api/v1/stor/files', 'StorControllerApiV1:showFiles')->setName('stor.api.files');
  // ajax co vypise vhodne idecka k vybranemu diru, pro copy move
  $this->get('/api/v1/stor/modalobjects', 'StorControllerApiV1:showModalObjects')->setName('stor.api.modal.objects');
  // ajax co vraci optiony v jsonu pro select 2 filtr
  $this->get('/api/v1/stor/filteroptions', 'StorControllerApiV1:showFilterOptions')->setName('stor.api.filter.options');

  
  
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
  
  // api permissions
  $this->delete('/api/v1/permissions/privileges/[{id}]', 'PermissionsControllerApiV1:deletePrivilegeApi')->setName('acl.api.privilege.delete');
  $this->delete('/api/v1/permissions/impaction/[{id}]', 'PermissionsControllerApiV1:deleteImpActionApi')->setName('acl.api.impaction.delete');
  $this->delete('/api/v1/permissions/action/[{id}]', 'PermissionsControllerApiV1:deleteActionApi')->setName('acl.api.action.delete');   // id neni byt soucasti name, protoze se pridava rucne v ajaxu za nej
  $this->put('/api/v1/permissions/changeaction/[{id}]', 'PermissionsControllerApiV1:changeActionApi')->setName('acl.api.action.update');
  
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
$app->get('/playground/pohadkar_platby/list', '\Glued\Playground\Pohadkar_platby:list')->setName('platbylist')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
$app->get('/playground/pohadkar_platby/new', '\Glued\Playground\Pohadkar_platby:form')->setName('platbynew')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
$app->post('/playground/pohadkar_platby/new', '\Glued\Playground\Pohadkar_platby:insert')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
$app->get('/playground/pohadkar_platby/prikaz[/{id}]', '\Glued\Playground\Pohadkar_platby:prikaz');

// pohadkar, moje testy
$app->get('/playground/pohadkar_testy/innodb', '\Glued\Playground\Pohadkar_testy:form')->setName('innodb')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
$app->post('/playground/pohadkar_testy/innodb', '\Glued\Playground\Pohadkar_testy:test')->add(new AuthMiddleware($container))->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container))->add($container->csrf);
// fb test sdk
$app->get('/playground/pohadkar_testy/sdkindustry', '\Glued\Playground\Pohadkar_testy:sdkindustry')->setName('sdkindustry')->add(new AuthMiddleware($container));
// test kombinace json schema formu a input masky
$app->get('/playground/pohadkar_testy/schema-mask', '\Glued\Playground\Pohadkar_testy:schema_mask_test')->setName('input_mask_test');
// test kombinace json schema a extras
$app->get('/playground/pohadkar_testy/schema-extras', '\Glued\Playground\Pohadkar_testy:schema_extras_test')->setName('rjsf_extras_test');
// test stackedit
$app->get('/playground/pohadkar_testy/stackedit', '\Glued\Playground\Pohadkar_testy:stackedit_test')->setName('stackedit_test');
// libovolny output, test ruznych veci udelanych v php
$app->get('/playground/pohadkar_testy/output1', '\Glued\Playground\Pohadkar_testy:test_output1')->setName('test.output1');


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