<?php

return [
    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'sandbox' => (bool) env('SSLCOMMERZ_SANDBOX', true),
        'timeout' => (int) env('SSLCOMMERZ_TIMEOUT', 15),
    ],
];