<?php

namespace App\Jobs;

use App\Models\ApiSyncLog;
use App\Services\FootballData\FootballDataSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncFixturesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $manual = false) {}

    public function handle(FootballDataSync $sync): void
    {
        $start = hrtime(true);

        try {
            $result = $sync->syncAll();

            ApiSyncLog::create([
                'type' => 'fixtures',
                'status' => 'success',
                'endpoint' => 'competitions/{code}/matches',
                'items_processed' => $result['matches'],
                'message' => "Toernooi, {$result['teams']} teams, {$result['matches']} wedstrijden en {$result['standings']} standen gesynchroniseerd.",
                'context' => ['teams' => $result['teams'], 'matches' => $result['matches'], 'standings' => $result['standings']],
                'duration_ms' => $this->elapsed($start),
                'triggered_manually' => $this->manual,
            ]);
        } catch (Throwable $e) {
            ApiSyncLog::create([
                'type' => 'fixtures',
                'status' => 'failed',
                'message' => $e->getMessage(),
                'duration_ms' => $this->elapsed($start),
                'triggered_manually' => $this->manual,
            ]);

            throw $e;
        }
    }

    protected function elapsed(int $start): int
    {
        return (int) ((hrtime(true) - $start) / 1e6);
    }
}
