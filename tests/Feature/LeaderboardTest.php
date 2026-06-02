<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Scoring\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private GameMatch $match;

    protected function setUp(): void
    {
        parent::setUp();

        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $home = Team::create(['tournament_id' => $tournament->id, 'name' => 'NED']);
        $away = Team::create(['tournament_id' => $tournament->id, 'name' => 'BRA']);
        $this->match = GameMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'kickoff_at' => now()->subDay(),
            'status' => 'FINISHED',
            'home_score' => 1,
            'away_score' => 0,
        ]);
    }

    private function predict(User $user, int $points, bool $exact = false, bool $outcome = true): void
    {
        Prediction::create([
            'user_id' => $user->id,
            'match_id' => $this->match->id,
            'home_score' => 1,
            'away_score' => 0,
            'points' => $points,
            'is_exact' => $exact,
            'is_correct_outcome' => $outcome,
        ]);
    }

    public function test_standings_order_by_points_then_exact_then_name(): void
    {
        $top = User::factory()->create(['name' => 'Carol']);
        $this->predict($top, 12, exact: true);

        $moreExact = User::factory()->create(['name' => 'Bob']);
        $this->predict($moreExact, 10, exact: true);

        $lessExact = User::factory()->create(['name' => 'Alice']);
        $this->predict($lessExact, 10, exact: false);

        $standings = app(LeaderboardService::class)->standings();

        $this->assertSame(['Carol', 'Bob', 'Alice'], $standings->pluck('name')->all());
        $this->assertSame([1, 2, 3], $standings->pluck('rank')->all());
    }

    public function test_alphabetical_tiebreaker_when_fully_equal(): void
    {
        $zoe = User::factory()->create(['name' => 'Zoe']);
        $anna = User::factory()->create(['name' => 'Anna']);
        $this->predict($zoe, 8, exact: false);
        $this->predict($anna, 8, exact: false);

        $standings = app(LeaderboardService::class)->standings();

        $this->assertSame('Anna', $standings->first()['name']);
    }

    public function test_blocked_users_are_excluded(): void
    {
        $active = User::factory()->create(['name' => 'Active']);
        $blocked = User::factory()->create(['name' => 'Blocked', 'blocked_at' => now()]);
        $this->predict($active, 5);
        $this->predict($blocked, 9);

        $names = app(LeaderboardService::class)->standings()->pluck('name')->all();

        $this->assertContains('Active', $names);
        $this->assertNotContains('Blocked', $names);
    }
}
