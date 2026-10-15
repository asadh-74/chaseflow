<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console & Scheduler Routes
|--------------------------------------------------------------------------
| The nightly automation: walk every overdue invoice through the reminder
| ladder. withoutOverlapping() makes overlapping cron triggers harmless.
*/

Schedule::command('chaseflow:process-overdue-invoices')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground();
