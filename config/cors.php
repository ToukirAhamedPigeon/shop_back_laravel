<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', '*')),
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
    'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', '*')),
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
];
