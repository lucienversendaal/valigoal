<?php

namespace App\Services\Scoring;

use App\Models\GameMatch;
use Illuminate\Support\Collection;

class PredictedStandingService
{
    /**
     * Build a group table from a user's predicted scores.
     *
     * Each match is expected to have its `predictions` relation eager-loaded
     * and already constrained to the relevant user (0 or 1 prediction).
     *
     * @param  Collection<int, GameMatch>  $matches
     * @return Collection<int, array<string, mixed>>
     */
    public function build(Collection $matches): Collection
    {
        $rows = [];

        $touch = function (array &$rows, $team) {
            if (! isset($rows[$team->id])) {
                $rows[$team->id] = [
                    'team' => $team,
                    'played' => 0, 'won' => 0, 'draw' => 0, 'lost' => 0,
                    'goals_for' => 0, 'goals_against' => 0, 'points' => 0,
                ];
            }
        };

        foreach ($matches as $match) {
            if (! $match->homeTeam || ! $match->awayTeam) {
                continue;
            }

            $touch($rows, $match->homeTeam);
            $touch($rows, $match->awayTeam);

            $prediction = $match->predictions->first();

            if (! $prediction) {
                continue;
            }

            $home = $match->home_team_id;
            $away = $match->away_team_id;
            $hs = (int) $prediction->home_score;
            $as = (int) $prediction->away_score;

            $rows[$home]['played']++;
            $rows[$away]['played']++;
            $rows[$home]['goals_for'] += $hs;
            $rows[$home]['goals_against'] += $as;
            $rows[$away]['goals_for'] += $as;
            $rows[$away]['goals_against'] += $hs;

            if ($hs > $as) {
                $rows[$home]['won']++;
                $rows[$away]['lost']++;
                $rows[$home]['points'] += 3;
            } elseif ($hs < $as) {
                $rows[$away]['won']++;
                $rows[$home]['lost']++;
                $rows[$away]['points'] += 3;
            } else {
                $rows[$home]['draw']++;
                $rows[$away]['draw']++;
                $rows[$home]['points']++;
                $rows[$away]['points']++;
            }
        }

        return collect($rows)
            ->map(function (array $row) {
                $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];

                return $row;
            })
            ->sort(function (array $a, array $b) {
                return [$b['points'], $b['goal_difference'], $b['goals_for'], $a['team']->name]
                    <=> [$a['points'], $a['goal_difference'], $a['goals_for'], $b['team']->name];
            })
            ->values()
            ->map(function (array $row, int $index) {
                $row['position'] = $index + 1;

                return $row;
            });
    }
}
