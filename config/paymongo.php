<?php

return [
    'secret_key' => env('PAYMONGO_SECRET_KEY'),
    'public_key' => env('PAYMONGO_PUBLIC_KEY'),
    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
    'success_url' => env('PAYMONGO_SUCCESS_URL'),
    'cancel_url' => env('PAYMONGO_CANCEL_URL'),
];
