<?php

// crypto config

return [
    'jwt' => [
        'secretKey' => $_ENV['JWT_SECRET'],
        'defaultTtl' => $_ENV['JWT_TTL'] ?? 3600,
        'algorithm' => $_ENV['JWT_ALGO'] ?? 'HS256'
    ],
    'hashing' => [
        'algorithm' => $_ENV['HASH_ALGO'] ?? PASSWORD_ARGON2ID,
        'options' => [
            'memory_cost' => $_ENV['HASH_MEMORY'] ?? 65536,
            'time_cost' => $_ENV['HASH_TIME'] ?? 4,
            'threads' => $_ENV['HASH_THREADS'] ?? 1
        ]
    ],
    'encryption' => [
        'cipher' => $_ENV['ENC_CIPHER'] ?? 'AES-256-CBC',
        'keyDerivation' => $_ENV['ENC_KEY_DERIVATION'] ?? 'sha256'
    ],
    'tokens' => [
        'csrfLength' => $_ENV['CSRF_LENGTH'] ?? 32,
        'apiKeyLength' => $_ENV['API_KEY_LENGTH'] ?? 64
    ]
];