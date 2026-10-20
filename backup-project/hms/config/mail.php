<?php

return [
    'default' => env('MAIL_MAILER', 'log'),
    'mailers' => [
        'log' => [
            'transport' => 'log',
        ],
    ],
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@hospital.local'),
        'name' => env('MAIL_FROM_NAME', 'Hospital Meal System'),
    ],
];
