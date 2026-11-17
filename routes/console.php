<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gamification:reconcile')->dailyAt('03:15');
Schedule::command('learning:redispatch-completions', [
    '--since' => now()->subDays(2)->toDateString(),
])->dailyAt('03:30');
