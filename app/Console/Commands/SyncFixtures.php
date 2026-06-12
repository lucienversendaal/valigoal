<?php

namespace App\Console\Commands;

use App\Jobs\SyncFixturesJob;
use Illuminate\Console\Command;

class SyncFixtures extends Command
{
    protected $signature = 'sync:fixtures {--manual : Markeer als handmatig getriggerd in de ApiSyncLogs} {--now : Draai direct (synchroon) i.p.v. via de queue}';

    protected $description = 'Ververst fixtures en teams van football-data';

    public function handle(): int
    {
        $job = new SyncFixturesJob(manual: (bool) $this->option('manual'));

        if ($this->option('now')) {
            dispatch_sync($job);
            $this->info('Fixtures-sync synchroon uitgevoerd.');
        } else {
            dispatch($job);
            $this->info('Fixtures-sync op de queue gezet.');
        }

        return self::SUCCESS;
    }
}
