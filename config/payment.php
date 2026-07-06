<?php

return [
    /*
     * Passerelle active : 'manual' | 'paydunya' | 'wave'
     * Modifiable via la variable d'environnement PAYMENT_GATEWAY.
     */
    'gateway' => env('PAYMENT_GATEWAY', 'manual'),

    'paydunya' => [
        'mode' => env('PAYDUNYA_MODE', 'sandbox'), // 'sandbox' | 'live'
        'master_key' => env('PAYDUNYA_MASTER_KEY', ''),
        'private_key' => env('PAYDUNYA_PRIVATE_KEY', ''),
        'public_key' => env('PAYDUNYA_PUBLIC_KEY', ''),
        'token' => env('PAYDUNYA_TOKEN', ''),
    ],

    'wave' => [
        'api_key' => env('WAVE_API_KEY', ''),
        'webhook_secret' => env('WAVE_WEBHOOK_SECRET', ''),
    ],
];
