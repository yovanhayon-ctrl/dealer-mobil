<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat H-1 test drive & servis (butuh cron `* * * * * php artisan schedule:run` di hosting; lihat docs/DEPLOY-CPANEL.md).
Schedule::command('reminders:send')->dailyAt(config('dealer.reminder_time'))->withoutOverlapping();
