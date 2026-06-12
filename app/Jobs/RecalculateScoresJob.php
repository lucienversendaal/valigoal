<?php

namespace App\Jobs;

use App\Models\ApiSyncLog;
use App\Models\Tournament;
use App\Services\Scoring\ScoreCalculationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RecalculateScoresJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tournament $tournament, public bool $manual = false) {}

    public function handle(ScoreCalculationService $scoring): void
    {
        $start = hrtime(true);

        try {
            $scored = $scoring->recalculateMatches($this->tournament);

            ApiSyncLog::create([
                'type' => 'scoring',
                'status' => 'success',
                'endpoint' => 'recalculateMatches',
                'items_processed' => $scored,
                'message' => "{$scored} voltooide wedstrijd(en) opnieuw gescoord voor toernooi \"{$this->tournament->name}\".",
                'context' => ['tournament_id' => $this->tournament->id, 'matches_scored' => $scored],
                'duration_ms' => (int) ((hrtime(true) - $start) / 1e6),
                'triggered_manually' => $this->manual,
            ]);
        } catch (Throwable $e) {
            ApiSyncLog::create([
                'type' => 'scoring',
                'status' => 'failed',
                'endpoint' => 'recalculateMatches',
                'message' => $e->getMessage(),
                'context' => ['tournament_id' => $this->tournament->id],
                'duration_ms' => (int) ((hrtime(true) - $start) / 1e6),
                'triggered_manually' => $this->manual,
            ]);

            throw $e;
        }
    }
}
