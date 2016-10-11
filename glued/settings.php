<?php

return [
    'settings' => [
        'displayErrorDetails' => true,

        // Database
        'db' => [
            'host' => '127.0.0.1',
            'database' => 'slim',
            'username' => 'killua',
            'password' => 'j340flsjssm_jaQA',
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci'
        ],

        // Monolog
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
    ],
];


