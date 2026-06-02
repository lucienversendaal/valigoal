<?php

namespace App\Services\Scoring;

use App\Models\User;
use Illuminate\Support\Collection;

class LeaderboardService
{
    /**
     * Build the overall standings, applying the tie-breaker order:
     * total points → most exact scores → most correct outcomes → name (A-Z).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function standings(): Collection
    {
        return User::query()
            ->whereNull('blocked_at')
            ->withSum('predictions as match_points', 'points')
            ->withSum('bonusPredictions as bonus_points', 'points')
            ->withCount([
                'predictions as exact_count' => fn ($q) => $q->where('is_exact', true),
                'predictions as outcome_count' => fn ($q) => $q->where('is_correct_outcome', true),
                'predictions as predictions_count',
            ])
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'name' => $user->name,
                'total_points' => (int) $user->match_points + (int) $user->bonus_points,
                'match_points' => (int) $user->match_points,
                'bonus_points' => (int) $user->bonus_points,
                'exact_count' => (int) $user->exact_count,
                'outcome_count' => (int) $user->outcome_count,
                'predictions_count' => (int) $user->predictions_count,
            ])
            ->sort(function (array $a, array $b) {
                return [$b['total_points'], $b['exact_count'], $b['outcome_count'], $a['name']]
                    <=> [$a['total_points'], $a['exact_count'], $a['outcome_count'], $b['name']];
            })
            ->values()
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    /**
     * The standings row for a single user (1-based rank), or null.
     *
     * @return array<string, mixed>|null
     */
    public function rowFor(User $user): ?array
    {
        return $this->standings()->firstWhere('user.id', $user->id);
    }
}
