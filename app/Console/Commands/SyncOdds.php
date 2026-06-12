<?php

namespace App\Console\Commands;

use App\Jobs\SyncOddsJob;
use Illuminate\Console\Command;

class SyncOdds extends Command
{
    protected $signature = 'sync:odds {--manual : Markeer als handmatig getriggerd in de ApiSyncLogs} {--now : Draai direct (synchroon) i.p.v. via de queue}';

    protected $description = 'Ververst de 1X2-odds van The Odds API';

    public function handle(): int
    {
        $job = new SyncOddsJob(manual: (bool) $this->option('manual'));

        if ($this->option('now')) {
            dispatch_sync($job);
            $this->info('Odds-sync synchroon uitgevoerd.');
        } else {
            dispatch($job);
            $this->info('Odds-sync op de queue gezet.');
        }

        return self::SUCCESS;
    }
}
