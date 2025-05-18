<?php

// Конфиг маршрутов

return [
    [
        'path' => '/artist/{id}/getInfo',
        'method' => 'GET',
        'handler' => '',
        'middlewares' => [
            \Rift\Validators\Validator::class
        ]
    ],
    [
        'path' => '/artist/{id}/getTopTracks',
        'method' => 'GET',
        'handler' => '',
        'middlewares' => [
            \Rift\Validators\Validator::class
        ]
    ]
];