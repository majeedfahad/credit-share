<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Budget check — every hour
Schedule::command('pay:check-budget')->hourly();

// Weekly report — Saturdays at 9:00 AM
Schedule::command('pay:weekly-report')->weeklyOn(6, '09:00');

// Monthly report — 25th of each month at 10:00 AM
Schedule::command('pay:monthly-report')->monthlyOn(25, '10:00');
