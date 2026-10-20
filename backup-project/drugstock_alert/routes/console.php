<?php

use App\Http\Services\DrugStockAlertAutomationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('drugstock:dispatch-alerts', function () {
    $result = app(DrugStockAlertAutomationService::class)->sweep();

    $this->info('Drug stock alerts processed.');
    $this->line('Created: ' . ($result['created'] ?? 0));
    $this->line('Sent: ' . ($result['sent'] ?? 0));
})->purpose('Scan drug stock alerts and send Telegram notifications');

Schedule::command('drugstock:dispatch-alerts')->hourly();
