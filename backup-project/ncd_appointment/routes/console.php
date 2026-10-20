<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncHosxpAppointmentsJob;

// Sync HOSxP appointments every 15 minutes
Schedule::job(new SyncHosxpAppointmentsJob)->everyFifteenMinutes();

