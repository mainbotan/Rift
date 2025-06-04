<?php

// db config

return [
    'driver' => $_ENV['DB_DRIVER'],
    'host' => $_ENV['DB_HOST'],
    'post' => $_ENV['DB_PORT'],
    'user' => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASSWORD'],
    'name' => $_ENV['DB_NAME'],

    // schemas initialization
    'configurators' => [
        'system' => App\Configurators\AppSystemConfigurator::class,
        'tenant' => App\Configurators\AppTenantConfigurator::class
    ]
];