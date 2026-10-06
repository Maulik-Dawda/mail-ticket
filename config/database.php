<?php

return [
    'driver' => 'sqlite', // 'sqlite' or 'mysql'
    
    'sqlite' => [
        'database' => __DIR__ . '/../database/database.sqlite',
    ],

    'mysql' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'mail_ticket',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ]
];
