<?php

return [
    'mode' => env('HOSXP_MODE', 'read_only'),
    'sync_interval_minutes' => (int) env('HOSXP_SYNC_INTERVAL_MINUTES', 10),
    'api_base_url' => env('HOSXP_API_BASE_URL'),
    'api_token' => env('HOSXP_API_TOKEN'),
    'patient_view' => env('HOSXP_PATIENT_VIEW', 'v_patient_meal_ready'),
    'queries' => [
        'patient_cache' => 'select * from hosxp_patient_view where updated_at >= :since',
    ],
];
