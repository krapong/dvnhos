<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\SendTelegramAlerts;

Schedule::command(SendTelegramAlerts::class)->dailyAt('08:00');
