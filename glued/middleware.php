<?php

// Glued's own middleware
$app->add(new \Glued\Middleware\ValidationErrorsMiddleware($container));
$app->add(new \Glued\Middleware\OldInputMiddleware($container));
$app->add(new \Glued\Middleware\CsrfViewMiddleware($container));

// Middleware installed with composer and pulled in in a DI contaner
$app->add($container->csrf);

