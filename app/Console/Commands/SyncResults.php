<?php

namespace App\Console\Commands;

use App\Jobs\SyncResultsJob;
use Illuminate\Console\Command;

class SyncResults extends Command
{
    protected $signature = 'sync:results {--manual : Markeer als handmatig getriggerd in de ApiSyncLogs} {--now : Draai direct (synchroon) i.p.v. via de queue}';

    protected $description = 'Haalt eindstanden op, kent punten toe en werkt topscorers/bonussen bij';

    public function handle(): int
    {
        $job = new SyncResultsJob(manual: (bool) $this->option('manual'));

        if ($this->option('now')) {
            dispatch_sync($job);
            $this->info('Results-sync synchroon uitgevoerd.');
        } else {
            dispatch($job);
            $this->info('Results-sync op de queue gezet.');
        }

        return self::SUCCESS;
    }
}
