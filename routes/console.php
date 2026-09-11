<?php

use App\Console\Commands\DeliverReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// One delivery run a day. Reminder::due() takes everything already past its
// time, so reminders missed since the last run — or while nothing was running
// at all — go out together with that morning's batch.
Schedule::command(DeliverReminders::class)->dailyAt('09:00')->withoutOverlapping();
