<?php

use App\Console\Commands\ProcessSubscriptionDunning;
use App\Console\Commands\SendSubscriptionExpiryReminders;
use App\Console\Commands\SendTrialExpiryReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendSubscriptionExpiryReminders::class)->dailyAt('09:00');
Schedule::command(SendTrialExpiryReminders::class)->dailyAt('09:00');
Schedule::command(ProcessSubscriptionDunning::class)->dailyAt('09:00');
