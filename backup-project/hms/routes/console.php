<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('hospital-meal:summary', function (): int {
    $summary = app(\App\Services\DailySummaryService::class)->buildForDate(now()->toDateString());
    $this->info(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
});

Artisan::command('hospital-meal:sync-hosxp', function (): int {
    $result = app(\App\Services\HosxpSyncService::class)->syncPatientCache();
    $this->info(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
});

Artisan::command('hospital-meal:telegram-send-due', function (): int {
    $result = app(\App\Services\TelegramScheduleService::class)->sendDue(now());
    $this->info(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
});

Artisan::command('hospital-meal:ensure-auth-users', function (): int {
    $roles = collect([
        ['key' => 'admin', 'name' => 'ผู้ดูแลระบบ', 'permissions' => ['*']],
        ['key' => 'nurse', 'name' => 'พยาบาล', 'permissions' => ['orders:create', 'orders:update', 'orders:submit']],
        ['key' => 'nutritionist', 'name' => 'นักโภชนาการ', 'permissions' => ['templates:manage', 'orders:review', 'sync:his']],
        ['key' => 'kitchen', 'name' => 'ห้องครัว', 'permissions' => ['orders:accept', 'orders:prepare']],
        ['key' => 'manager', 'name' => 'ผู้บริหาร', 'permissions' => ['dashboards:view']],
    ])->mapWithKeys(fn (array $role) => [
        $role['key'] => \App\Models\Role::updateOrCreate(['key' => $role['key']], $role),
    ]);

    $users = [
        ['name' => 'System Admin', 'email' => 'admin@hospital.local', 'username' => 'admin', 'position' => 'ผู้ดูแลระบบ', 'role' => 'admin'],
        ['name' => 'Nurse User', 'email' => 'nurse@hospital.local', 'username' => 'nurse', 'position' => 'พยาบาล', 'role' => 'nurse'],
        ['name' => 'Nutrition User', 'email' => 'nutrition@hospital.local', 'username' => 'nutrition', 'position' => 'โภชนาการ', 'role' => 'nutritionist'],
        ['name' => 'Kitchen User', 'email' => 'kitchen@hospital.local', 'username' => 'kitchen', 'position' => 'ห้องครัว', 'role' => 'kitchen'],
        ['name' => 'Manager User', 'email' => 'manager@hospital.local', 'username' => 'manager', 'position' => 'ผู้บริหาร', 'role' => 'manager'],
    ];

    foreach ($users as $user) {
        \App\Models\User::updateOrCreate(
            ['email' => $user['email']],
            [
                'name' => $user['name'],
                'username' => $user['username'],
                'position' => $user['position'],
                'approval_status' => 'active',
                'approved_at' => now(),
                'rejected_at' => null,
                'approval_note' => null,
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role_id' => $roles[$user['role']]->id,
            ],
        );
    }

    $this->info('Ensured auth roles and demo users. Default password: password');

    return self::SUCCESS;
});

Artisan::command('hospital-meal:inspect-his', function (): int {
    $keywords = ['patient', 'ipt', 'ward', 'bed', 'allerg', 'food', 'diet', 'nutrition'];
    $query = \Illuminate\Support\Facades\DB::connection('hosxp')
        ->table('information_schema.tables')
        ->select(['table_name', 'table_type'])
        ->whereRaw('table_schema = database()')
        ->where(function ($query) use ($keywords): void {
            foreach ($keywords as $keyword) {
                $query->orWhere('table_name', 'like', "%{$keyword}%");
            }
        })
        ->orderBy('table_name')
        ->limit(80)
        ->get();

    $this->info(json_encode($query, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
});

Artisan::command('hospital-meal:inspect-his-columns {tables*}', function (array $tables): int {
    $columns = \Illuminate\Support\Facades\DB::connection('hosxp')
        ->table('information_schema.columns')
        ->select(['table_name', 'column_name', 'data_type'])
        ->whereRaw('table_schema = database()')
        ->whereIn('table_name', $tables)
        ->orderBy('table_name')
        ->orderBy('ordinal_position')
        ->get();

    $this->info(json_encode($columns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
});

Artisan::command('hospital-meal:inspect-his-nutrition', function (): int {
    $tables = \Illuminate\Support\Facades\DB::connection('hosxp')
        ->table('information_schema.tables')
        ->select(['table_name', 'table_type'])
        ->whereRaw('table_schema = database()')
        ->where('table_name', 'like', 'nutrition\_%')
        ->orderBy('table_name')
        ->get();

    $this->info(json_encode($tables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
});

Artisan::command('hospital-meal:inspect-his-nutrition-days', function (): int {
    $foodDays = \Illuminate\Support\Facades\DB::connection('hosxp')
        ->table('nutrition_food_day')
        ->leftJoin('nutrition_food_sub_day', 'nutrition_food_sub_day.nutrition_food_sub_day_id', '=', 'nutrition_food_day.nutrition_food_sub_day_id')
        ->select([
            'nutrition_food_day.nutrition_food_day_id',
            'nutrition_food_day.nutrition_food_day_name',
            'nutrition_food_day.nutrition_food_sub_day_id',
            'nutrition_food_sub_day.nutrition_food_sub_day_name',
            'nutrition_food_day.active',
        ])
        ->orderBy('nutrition_food_day.nutrition_food_day_id')
        ->get();

    $usedDays = \Illuminate\Support\Facades\DB::connection('hosxp')
        ->table('nutrition_food_ord_detail as detail')
        ->leftJoin('nutrition_food_day as food_day', 'food_day.nutrition_food_day_id', '=', 'detail.nutrition_food_day_id')
        ->leftJoin('nutrition_food_sub_day as sub_day', 'sub_day.nutrition_food_sub_day_id', '=', 'detail.nutrition_food_sub_day_id')
        ->select([
            'detail.nutrition_food_day_id',
            'food_day.nutrition_food_day_name',
            'detail.nutrition_food_sub_day_id',
            'sub_day.nutrition_food_sub_day_name',
            \Illuminate\Support\Facades\DB::raw('count(*) as total'),
        ])
        ->groupBy([
            'detail.nutrition_food_day_id',
            'food_day.nutrition_food_day_name',
            'detail.nutrition_food_sub_day_id',
            'sub_day.nutrition_food_sub_day_name',
        ])
        ->orderBy('detail.nutrition_food_day_id')
        ->orderBy('detail.nutrition_food_sub_day_id')
        ->get();

    $this->info(json_encode([
        'nutrition_food_day' => $foodDays,
        'used_in_order_detail' => $usedDays,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
});

Schedule::command('hospital-meal:summary')->dailyAt('05:00');
Schedule::command('hospital-meal:sync-hosxp')->everyTenMinutes();
Schedule::command('hospital-meal:telegram-send-due')->everyMinute();
