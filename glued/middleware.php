<?php

// Glued's own middleware
$app->add(new \Glued\Middleware\Forms\ValidationErrorsMiddleware($container));
$app->add(new \Glued\Middleware\Forms\OldInputMiddleware($container));
//$app->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container));

// To enable the CSRF middleware for all routes, uncomment the lines below.
// Beware that as long as whitelisting on slim/csfr is not implemented,
// enabling CSRF on API routes will f*ck up the API's POST and PUT methods.

// TODO needs to be replaced by grouping in routes.php

//$app->add($container->csrf);

