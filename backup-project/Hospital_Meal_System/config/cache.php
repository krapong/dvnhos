<?php

return [
    'default' => env('CACHE_STORE', 'file'),
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'lock_table' => 'cache_locks',
        ],
    ],
    'prefix' => env('CACHE_PREFIX', str_replace('-', '_', env('APP_NAME', 'hospital_meal_system')).'_cache'),
];
