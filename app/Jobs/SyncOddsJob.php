<?php

namespace App\Jobs;

use App\Models\ApiSyncLog;
use App\Models\Tournament;
use App\Services\Odds\OddsApiSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncOddsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $manual = false) {}

    public function handle(OddsApiSync $sync): void
    {
        $tournament = Tournament::where('is_active', true)->first();

        if (! $tournament) {
            return;
        }

        $start = hrtime(true);

        try {
            $result = $sync->sync($tournament);

            ApiSyncLog::create([
                'type' => 'odds',
                'status' => 'success',
                'endpoint' => 'the-odds-api.com/v4/sports/{sport}/odds',
                'items_processed' => $result['matched'],
                'message' => "{$result['matched']} wedstrijden voorzien van odds ({$result['events']} events opgehaald).",
                'context' => $result,
                'duration_ms' => (int) ((hrtime(true) - $start) / 1e6),
                'triggered_manually' => $this->manual,
            ]);
        } catch (Throwable $e) {
            ApiSyncLog::create([
                'type' => 'odds',
                'status' => 'failed',
                'message' => $e->getMessage(),
                'duration_ms' => (int) ((hrtime(true) - $start) / 1e6),
                'triggered_manually' => $this->manual,
            ]);

            throw $e;
        }
    }
}
