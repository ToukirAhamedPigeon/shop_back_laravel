<?php

return [
    'paths' => ['api/*', 'csrf/token', 'sanctum/csrf-cookie', '*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:4200',
        'http://localhost:3000',
        'http://localhost:8000',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['X-CSRF-TOKEN'],
    'max_age' => 0,
    'supports_credentials' => true,
];
