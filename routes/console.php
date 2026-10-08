<?php

use App\Support\SyncStamp;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reload every open page at midnight so the new day shows without a tap
Schedule::call(fn () => SyncStamp::bump())->name('refresh-open-pages')->dailyAt('00:00');
