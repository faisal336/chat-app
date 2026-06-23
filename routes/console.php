<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Drain the database queue once per minute. Required on Hostinger shared hosting
// because there is no Supervisor and `queue:work` cannot run as a daemon.
Schedule::command('queue:work --queue=default --stop-when-empty --max-time=50 --max-jobs=200')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Prune Laravel's failed_jobs and sessions tables weekly.
Schedule::command('queue:prune-failed --hours=168')->weekly();
Schedule::command('session:prune')->daily();
