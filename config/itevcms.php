<?php

return [

    'citation_overdue_days' => (int) env('ITEVCMS_CITATION_OVERDUE_DAYS', 30),

    'clamping_eligible_days' => (int) env('ITEVCMS_CLAMPING_ELIGIBLE_DAYS', 30),

    'app_name' => env('APP_NAME', 'TEMs'),

    'impounding' => [
        'grace_hours' => 24,
        'default_towing_fee' => 2000.00,
        'default_storage_fee_per_day' => 500.00,
        'default_admin_fee' => 500.00,
        'violation_fees' => [
            'unregistered' => [
                'towing_fee' => 2000.00,
                'storage_fee_per_day' => 500.00,
                'admin_fee' => 500.00,
            ],
            'expired_plate' => [
                'towing_fee' => 1500.00,
                'storage_fee_per_day' => 400.00,
                'admin_fee' => 500.00,
            ],
            'no_license' => [
                'towing_fee' => 2000.00,
                'storage_fee_per_day' => 500.00,
                'admin_fee' => 500.00,
            ],
            'default' => [
                'towing_fee' => 2000.00,
                'storage_fee_per_day' => 500.00,
                'admin_fee' => 500.00,
            ],
        ],
    ],

];
