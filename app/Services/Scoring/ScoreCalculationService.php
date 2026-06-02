<?php

namespace App\Services\Scoring;

use App\Enums\BonusType;
use App\Models\BonusPrediction;
use App\Models\GameMatch;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScoreCalculationService
{
    public const POINTS_EXACT = 5;

    public const POINTS_OUTCOME = 3;

    public const POINTS_GOAL_DIFFERENCE = 1;

    /**
     * Score a single prediction against the actual result.
     */
    public function score(int $predHome, int $predAway, int $actualHome, int $actualAway): ScoreResult
    {
        $isExact = $predHome === $actualHome && $predAway === $actualAway;

        $predDiff = $predHome - $predAway;
        $actualDiff = $actualHome - $actualAway;

        $isCorrectOutcome = $this->sign($predDiff) === $this->sign($actualDiff);
        $isCorrectGoalDifference = $predDiff === $actualDiff;

        $points = match (true) {
            $isExact => self::POINTS_EXACT,
            $isCorrectOutcome => self::POINTS_OUTCOME + ($isCorrectGoalDifference ? self::POINTS_GOAL_DIFFERENCE : 0),
            default => 0,
        };

        return new ScoreResult($points, $isExact, $isCorrectOutcome, $isCorrectGoalDifference);
    }

    /**
     * Award points to every prediction on a finished match and flag it as scored.
     */
    public function awardMatch(GameMatch $match): void
    {
        if (! $match->hasResult()) {
            return;
        }

        DB::transaction(function () use ($match) {
            $match->predictions()->each(function ($prediction) use ($match) {
                $result = $this->score(
                    $prediction->home_score,
                    $prediction->away_score,
                    $match->home_score,
                    $match->away_score,
                );

                $prediction->forceFill([
                    'points' => $result->points,
                    'is_exact' => $result->isExact,
                    'is_correct_outcome' => $result->isCorrectOutcome,
                    'is_correct_goal_difference' => $result->isCorrectGoalDifference,
                ])->save();
            });

            $match->forceFill(['points_awarded' => true])->save();
        });
    }

    /**
     * Award bonus points once the tournament outcome is known.
     */
    public function awardBonuses(Tournament $tournament): int
    {
        $finalists = array_filter([$tournament->winner_team_id, $tournament->runner_up_team_id]);
        $topScorer = $tournament->top_scorer_name;
        $awarded = 0;

        $tournament->bonusPredictions()->get()->each(function (BonusPrediction $bonus) use ($tournament, $finalists, $topScorer, &$awarded) {
            $correct = match ($bonus->type) {
                BonusType::Winner => $bonus->team_id !== null && $bonus->team_id === $tournament->winner_team_id,
                BonusType::Finalist => $bonus->team_id !== null && in_array($bonus->team_id, $finalists, true),
                BonusType::TopScorer => $topScorer !== null
                    && $bonus->player_name !== null
                    && Str::lower(trim($bonus->player_name)) === Str::lower(trim($topScorer)),
            };

            $bonus->forceFill([
                'is_correct' => $correct,
                'points' => $correct ? $bonus->type->points() : 0,
            ])->save();

            if ($correct) {
                $awarded++;
            }
        });

        return $awarded;
    }

    protected function sign(int $value): int
    {
        return $value <=> 0;
    }
}
