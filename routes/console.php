<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Jadwal pengecekan otomatis setiap tengah malam
Schedule::command('piket:check-missed')->dailyAt('00:01');
Schedule::command('piket:check-replacement-expiry')->dailyAt('00:05');
Schedule::command('piket:check-redemption-expiry')->dailyAt('00:10');
Schedule::command('piket:check-semester-end')->dailyAt('00:15');
