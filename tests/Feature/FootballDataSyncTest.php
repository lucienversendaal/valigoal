<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Tournament;
use App\Models\User;
use App\Services\FootballData\FootballDataSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FootballDataSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_results_imports_final_scores_and_awards_points(): void
    {
        Http::fake([
            'api.football-data.org/*' => Http::response([
                'matches' => [[
                    'id' => 100,
                    'status' => 'FINISHED',
                    'utcDate' => '2026-06-12T19:00:00Z',
                    'stage' => 'GROUP_STAGE',
                    'group' => 'Group A',
                    'matchday' => 1,
                    'homeTeam' => ['id' => 1, 'name' => 'Nederland', 'tla' => 'NED'],
                    'awayTeam' => ['id' => 2, 'name' => 'Brazilië', 'tla' => 'BRA'],
                    'score' => ['winner' => 'HOME_TEAM', 'fullTime' => ['home' => 2, 'away' => 1]],
                ]],
            ]),
        ]);

        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $match = GameMatch::create([
            'tournament_id' => $tournament->id,
            'external_id' => 100,
            'kickoff_at' => '2026-06-12 19:00:00',
            'status' => 'TIMED',
        ]);

        $exactUser = User::factory()->create();
        Prediction::create(['user_id' => $exactUser->id, 'match_id' => $match->id, 'home_score' => 2, 'away_score' => 1]);

        $outcomeUser = User::factory()->create();
        Prediction::create(['user_id' => $outcomeUser->id, 'match_id' => $match->id, 'home_score' => 3, 'away_score' => 0]);

        $result = app(FootballDataSync::class)->syncResults($tournament);

        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['scored']);

        $match->refresh();
        $this->assertSame(2, $match->home_score);
        $this->assertSame(1, $match->away_score);
        $this->assertTrue($match->points_awarded);

        $this->assertSame(5, Prediction::where('user_id', $exactUser->id)->first()->points);
        $this->assertSame(3, Prediction::where('user_id', $outcomeUser->id)->first()->points);
    }

    public function test_results_are_not_awarded_twice(): void
    {
        Http::fake([
            'api.football-data.org/*' => Http::response([
                'matches' => [[
                    'id' => 100,
                    'status' => 'FINISHED',
                    'utcDate' => '2026-06-12T19:00:00Z',
                    'homeTeam' => ['id' => 1, 'name' => 'Nederland', 'tla' => 'NED'],
                    'awayTeam' => ['id' => 2, 'name' => 'Brazilië', 'tla' => 'BRA'],
                    'score' => ['winner' => 'HOME_TEAM', 'fullTime' => ['home' => 1, 'away' => 0]],
                ]],
            ]),
        ]);

        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        GameMatch::create(['tournament_id' => $tournament->id, 'external_id' => 100, 'kickoff_at' => now()->subDay(), 'status' => 'TIMED']);

        $sync = app(FootballDataSync::class);
        $first = $sync->syncResults($tournament);
        $second = $sync->syncResults($tournament);

        $this->assertSame(1, $first['scored']);
        $this->assertSame(0, $second['scored']);
    }
}
