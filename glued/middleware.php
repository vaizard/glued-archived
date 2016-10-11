<?php

// Glued's own middleware
$app->add(new \Glued\Middleware\Forms\ValidationErrorsMiddleware($container));
$app->add(new \Glued\Middleware\Forms\OldInputMiddleware($container));
$app->add(new \Glued\Middleware\Forms\CsrfViewMiddleware($container));

// Middleware installed with composer and pulled in in a DI contaner
$app->add($container->csrf);

