<?php

use App\Console\Commands\ExpireTrials;
use App\Console\Commands\ProcessSubscriptionDunning;
use App\Console\Commands\SendSubscriptionExpiryReminders;
use App\Console\Commands\SendTrialExpiryReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ExpireTrials::class)->dailyAt('06:00');
Schedule::command(SendSubscriptionExpiryReminders::class)->dailyAt('09:00');
Schedule::command(SendTrialExpiryReminders::class)->dailyAt('09:00');
Schedule::command(ProcessSubscriptionDunning::class)->dailyAt('09:00');

// Sauvegardes automatisées
Schedule::command('backup:run --only-db')->dailyAt('01:00');
Schedule::command('backup:run --only-files')->weeklyOn(0, '02:00'); // dimanche 02h
Schedule::command('backup:clean')->dailyAt('03:00');
Schedule::command('backup:monitor')->dailyAt('09:30');
