<?php

namespace Tests\Feature;

use App\Enums\BonusType;
use App\Models\BonusPrediction;
use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Scoring\ScoreCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BonusAndOutcomeScoringTest extends TestCase
{
    use RefreshDatabase;

    private Tournament $tournament;

    private Team $home;

    private Team $away;

    private ScoreCalculationService $scoring;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $this->home = Team::create(['tournament_id' => $this->tournament->id, 'name' => 'NED']);
        $this->away = Team::create(['tournament_id' => $this->tournament->id, 'name' => 'ARG']);
        $this->scoring = app(ScoreCalculationService::class);
    }

    private function final(string $winner): GameMatch
    {
        return GameMatch::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'stage' => 'FINAL',
            'kickoff_at' => now()->subHours(2),
            'status' => 'FINISHED',
            'home_score' => 1,
            'away_score' => 0,
            'winner' => $winner,
        ]);
    }

    public function test_resolve_final_outcome_sets_winner_and_runner_up(): void
    {
        $this->final('HOME_TEAM');

        $this->assertTrue($this->scoring->resolveFinalOutcome($this->tournament));
        $this->assertSame($this->home->id, $this->tournament->winner_team_id);
        $this->assertSame($this->away->id, $this->tournament->runner_up_team_id);
    }

    public function test_resolve_final_outcome_returns_false_without_a_decided_winner(): void
    {
        $this->final('DRAW');

        $this->assertFalse($this->scoring->resolveFinalOutcome($this->tournament));
        $this->assertNull($this->tournament->winner_team_id);
    }

    public function test_award_bonuses_scores_winner_finalist_and_top_scorer(): void
    {
        $this->final('HOME_TEAM');
        $this->scoring->resolveFinalOutcome($this->tournament);
        $this->tournament->forceFill(['top_scorer_name' => 'Lionel Messi'])->save();

        $user = User::factory()->create();
        BonusPrediction::create([
            'tournament_id' => $this->tournament->id,
            'user_id' => $user->id,
            'type' => BonusType::Winner,
            'team_id' => $this->home->id, // correct
        ]);
        BonusPrediction::create([
            'tournament_id' => $this->tournament->id,
            'user_id' => $user->id,
            'type' => BonusType::Finalist,
            'team_id' => $this->away->id, // runner-up counts as finalist
        ]);
        BonusPrediction::create([
            'tournament_id' => $this->tournament->id,
            'user_id' => $user->id,
            'type' => BonusType::TopScorer,
            'player_name' => 'lionel messi', // case-insensitive match
        ]);

        $correct = $this->scoring->awardBonuses($this->tournament->fresh());

        $this->assertSame(3, $correct);
        $this->assertSame(15 + 10 + 10, (int) BonusPrediction::where('user_id', $user->id)->sum('points'));
    }

    public function test_recalculate_matches_rescore_after_a_corrected_result(): void
    {
        $match = GameMatch::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'stage' => 'GROUP_STAGE',
            'kickoff_at' => now()->subHours(2),
            'status' => 'FINISHED',
            'home_score' => 1,
            'away_score' => 1,
        ]);

        $user = User::factory()->create();
        Prediction::create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'home_score' => 2,
            'away_score' => 2, // correct outcome (draw), no exact tally → 3 pts
        ]);

        $this->scoring->awardMatch($match);
        $this->assertSame(3, (int) Prediction::where('match_id', $match->id)->sum('points'));

        // Result corrected to an exact 2-2 → prediction should now score 5.
        $match->forceFill(['home_score' => 2, 'away_score' => 2])->save();

        $rescored = $this->scoring->recalculateMatches($this->tournament);

        $this->assertSame(1, $rescored);
        $this->assertSame(5, (int) Prediction::where('match_id', $match->id)->sum('points'));
    }
}
