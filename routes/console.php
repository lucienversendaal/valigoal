<?php

use App\Jobs\SyncFixturesJob;
use App\Jobs\SyncOddsJob;
use App\Jobs\SyncResultsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Refresh fixtures/teams once a day, outside match hours.
Schedule::job(new SyncFixturesJob)->dailyAt('04:00');

// Pull results and award points regularly; ~2 calls per run keeps us
// comfortably within football-data's 10 calls/minute free-tier limit.
Schedule::job(new SyncResultsJob)->everyThirtyMinutes();

// Refresh 1X2 odds a few times a day (The Odds API free tier is request-limited).
Schedule::job(new SyncOddsJob)->everySixHours();

// Announce newly opened knockout rounds and remind participants who still owe
// predictions, 24h + 2h before each round's deadline (its first kickoff).
Schedule::command('predictions:remind')->hourly()->withoutOverlapping();
