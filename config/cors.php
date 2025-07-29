<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://josemanuelcruzcristales-777.github.io',
        'https://josemanuelcruzcristales-777.github.io/login-app',
        'https://josemanuelcruzcristales-777.github.io/login-app/*',
        'http://localhost:4200',
        'http://127.0.0.1:4200',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];