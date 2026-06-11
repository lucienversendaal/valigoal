<?php

namespace App\Jobs;

use App\Models\ApiSyncLog;
use App\Models\Tournament;
use App\Services\FootballData\FootballDataSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncResultsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $manual = false) {}

    public function handle(FootballDataSync $sync): void
    {
        $tournament = Tournament::where('is_active', true)->first();

        if (! $tournament) {
            return;
        }

        $start = hrtime(true);

        try {
            $result = $sync->syncResults($tournament);
            $scorers = $sync->syncScorers($tournament);
            $bonuses = $sync->finalizeBonuses($tournament);

            $bonusMessage = $bonuses === null
                ? 'bonussen nog niet toegekend (winnaar/topscorer onbekend)'
                : "{$bonuses} bonussen goed gerekend";

            ApiSyncLog::create([
                'type' => 'results',
                'status' => 'success',
                'endpoint' => 'competitions/{code}/matches?status=FINISHED',
                'items_processed' => $result['updated'],
                'message' => "{$result['updated']} eindstanden verwerkt, {$result['scored']} wedstrijden gescoord, {$scorers} topscorers bijgewerkt, {$bonusMessage}.",
                'context' => $result + ['scorers' => $scorers, 'bonuses' => $bonuses],
                'duration_ms' => $this->elapsed($start),
                'triggered_manually' => $this->manual,
            ]);
        } catch (Throwable $e) {
            ApiSyncLog::create([
                'type' => 'results',
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
