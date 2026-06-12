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
    public const int POINTS_EXACT = 5;

    public const int POINTS_OUTCOME = 3;

    public const int POINTS_TEAM_SCORE = 1;

    /**
     * Score a single prediction against the actual result.
     *
     * - Exacte uitslag: 5 punten.
     * - Juiste uitkomst (winst/gelijk/verlies): 3 punten.
     * - +1 punt als je het exacte aantal goals van minstens één team goed had
     *   (thuis óf uit). Dit telt óók als de uitkomst fout is: voorspel je 1-1
     *   terwijl het 2-1 wordt, dan klopt het uitdoelpunt (1) en krijg je +1;
     *   voorspel je 2-2, dan klopt het thuisdoelpunt (2) en krijg je óók +1.
     *   Bovenop een juiste uitkomst is het een bonus; bij een exacte uitslag
     *   zit het al in de 5 punten.
     */
    public function score(int $predHome, int $predAway, int $actualHome, int $actualAway): ScoreResult
    {
        $isExact = $predHome === $actualHome && $predAway === $actualAway;

        $isCorrectOutcome = $this->sign($predHome - $predAway) === $this->sign($actualHome - $actualAway);

        $isCorrectTeamScore = $predHome === $actualHome || $predAway === $actualAway;

        $points = match (true) {
            $isExact => self::POINTS_EXACT,
            $isCorrectOutcome => self::POINTS_OUTCOME + ($isCorrectTeamScore ? self::POINTS_TEAM_SCORE : 0),
            $isCorrectTeamScore => self::POINTS_TEAM_SCORE,
            default => 0,
        };

        return new ScoreResult($points, $isExact, $isCorrectOutcome, $isCorrectTeamScore);
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
                    'is_correct_team_score' => $result->isCorrectTeamScore,
                ])->save();
            });

            $match->forceFill(['points_awarded' => true])->save();
        });
    }

    /**
     * Re-score every finished match of a tournament, ignoring the
     * `points_awarded` guard. Use after a result was corrected.
     *
     * @return int Number of matches (re)scored.
     */
    public function recalculateMatches(Tournament $tournament): int
    {
        $count = 0;

        $tournament->matches()->get()->each(function (GameMatch $match) use (&$count) {
            if ($match->hasResult()) {
                $this->awardMatch($match);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Derive winner and runner-up from the final and persist them on the
     * tournament. Returns true once a winner could be resolved.
     */
    public function resolveFinalOutcome(Tournament $tournament): bool
    {
        $final = $tournament->matches()->where('stage', 'FINAL')->first();

        if (! $final || ! $final->hasResult()) {
            return false;
        }

        [$winner, $runnerUp] = match ($final->winner) {
            'HOME_TEAM' => [$final->home_team_id, $final->away_team_id],
            'AWAY_TEAM' => [$final->away_team_id, $final->home_team_id],
            default => [null, null], // draw/penalties without a decided winner
        };

        if (! $winner) {
            return false;
        }

        $tournament->forceFill([
            'winner_team_id' => $winner,
            'runner_up_team_id' => $runnerUp,
        ])->save();

        return true;
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
