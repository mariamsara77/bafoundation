<?php

$frontendOrigins = array_values(array_filter(array_map(
    static fn (string $origin): string => rtrim(trim($origin), '/'),
    explode(',', (string) env('FRONTEND_URLS', ''))
)));

if ($frontendOrigins === []) {
    $singleOrigin = trim((string) env('FRONTEND_URL', ''));
    if ($singleOrigin !== '') {
        $frontendOrigins[] = rtrim($singleOrigin, '/');
    }
}

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_merge(
        $frontendOrigins,
        [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
        ],
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];
