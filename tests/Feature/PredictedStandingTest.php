<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Scoring\PredictedStandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictedStandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_group_table_from_predicted_scores(): void
    {
        $t = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $a = Team::create(['tournament_id' => $t->id, 'name' => 'Alpha']);
        $b = Team::create(['tournament_id' => $t->id, 'name' => 'Bravo']);
        $c = Team::create(['tournament_id' => $t->id, 'name' => 'Charlie']);

        $make = fn ($home, $away) => GameMatch::create([
            'tournament_id' => $t->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'group' => 'GROUP_A',
            'kickoff_at' => now()->addDays(2),
            'status' => 'TIMED',
        ]);

        $ab = $make($a, $b);
        $ac = $make($a, $c);
        $bc = $make($b, $c);

        $user = User::factory()->create();
        Prediction::create(['user_id' => $user->id, 'match_id' => $ab->id, 'home_score' => 2, 'away_score' => 0]);
        Prediction::create(['user_id' => $user->id, 'match_id' => $ac->id, 'home_score' => 1, 'away_score' => 0]);
        Prediction::create(['user_id' => $user->id, 'match_id' => $bc->id, 'home_score' => 3, 'away_score' => 0]);

        $matches = GameMatch::with(['homeTeam', 'awayTeam', 'predictions' => fn ($q) => $q->where('user_id', $user->id)])->get();

        $table = app(PredictedStandingService::class)->build($matches);

        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], $table->pluck('team.name')->all());
        $this->assertSame([6, 3, 0], $table->pluck('points')->all());
        $this->assertSame([1, 2, 3], $table->pluck('position')->all());

        $alpha = $table->firstWhere('team.name', 'Alpha');
        $this->assertSame(2, $alpha['played']);
        $this->assertSame(3, $alpha['goal_difference']);
    }

    public function test_unpredicted_matches_do_not_count(): void
    {
        $t = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $a = Team::create(['tournament_id' => $t->id, 'name' => 'Alpha']);
        $b = Team::create(['tournament_id' => $t->id, 'name' => 'Bravo']);
        GameMatch::create([
            'tournament_id' => $t->id, 'home_team_id' => $a->id, 'away_team_id' => $b->id,
            'group' => 'GROUP_A', 'kickoff_at' => now()->addDay(), 'status' => 'TIMED',
        ]);

        $matches = GameMatch::with(['homeTeam', 'awayTeam', 'predictions'])->get();
        $table = app(PredictedStandingService::class)->build($matches);

        $this->assertCount(2, $table);
        $this->assertSame([0, 0], $table->pluck('points')->all());
        $this->assertSame([0, 0], $table->pluck('played')->all());
    }
}
