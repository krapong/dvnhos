<?php

return [
    'meals' => ['breakfast', 'lunch', 'dinner'],
    'statuses' => [
        'draft',
        'submitted',
        'reviewing',
        'approved',
        'accepted',
        'preparing',
        'tray_ready',
        'delivering',
        'delivered',
        'ward_received',
        'completed',
        'cancelled',
        'rejected',
        'need_clarification',
        'stat',
    ],
    'meal_cutoffs' => [
        'night' => env('MEAL_CUTOFF_NIGHT', '05:00'),
        'breakfast' => env('MEAL_CUTOFF_BREAKFAST', '06:30'),
        'lunch' => env('MEAL_CUTOFF_LUNCH', '10:30'),
        'dinner' => env('MEAL_CUTOFF_DINNER', '16:30'),
    ],
];
