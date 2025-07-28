<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://josemanuelcruzcristales-777.github.io',
        'https://josemanuelcruzcristales-777.github.io/login-app',
        'https://josemanuelcruzcristales-777.github.io/login-app/*',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];