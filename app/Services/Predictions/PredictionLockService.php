<?php

namespace App\Services\Predictions;

use App\Exceptions\PredictionLockedException;
use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\PredictionAuditLog;
use App\Models\User;
use Illuminate\Support\Collection;

class PredictionLockService
{
    /**
     * Predictions can be created or changed up to the local kickoff time.
     */
    public function canEdit(GameMatch $match): bool
    {
        return $match->isOpen();
    }

    /**
     * Predictions stay secret until the match locks at kickoff. After that
     * they become visible to all participants.
     */
    public function predictionsAreVisible(GameMatch $match): bool
    {
        return $match->isLocked();
    }

    /**
     * Other participants' predictions, only once the match has locked.
     *
     * @return Collection<int, Prediction>
     */
    public function visiblePredictions(GameMatch $match): Collection
    {
        if (! $this->predictionsAreVisible($match)) {
            return collect();
        }

        return $match->predictions()->with('user')->get();
    }

    /**
     * Store or update a participant's prediction, enforcing the lock and
     * writing an audit log entry.
     *
     * @throws PredictionLockedException
     */
    public function save(User $user, GameMatch $match, int $homeScore, int $awayScore, ?string $ip = null): Prediction
    {
        if (! $this->canEdit($match)) {
            throw new PredictionLockedException;
        }

        $existing = Prediction::query()
            ->where('user_id', $user->id)
            ->where('match_id', $match->id)
            ->first();

        $old = $existing
            ? ['home_score' => $existing->home_score, 'away_score' => $existing->away_score]
            : null;

        $prediction = Prediction::updateOrCreate(
            ['user_id' => $user->id, 'match_id' => $match->id],
            ['home_score' => $homeScore, 'away_score' => $awayScore],
        );

        PredictionAuditLog::create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'action' => $existing ? 'updated' : 'created',
            'old_values' => $old,
            'new_values' => ['home_score' => $homeScore, 'away_score' => $awayScore],
            'ip_address' => $ip,
        ]);

        return $prediction;
    }
}
