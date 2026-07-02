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

    public function test_penalty_shootout_stores_the_90_minute_score_and_scores_against_it(): void
    {
        // football-data telt de strafschoppen mee in fullTime (1-1 wordt 4-5).
        // We willen tegen de 90-minutenstand (regularTime) afrekenen.
        Http::fake([
            'api.football-data.org/*' => Http::response([
                'matches' => [[
                    'id' => 537415,
                    'status' => 'FINISHED',
                    'utcDate' => '2026-06-29T20:30:00Z',
                    'stage' => 'LAST_16',
                    'homeTeam' => ['id' => 1, 'name' => 'Germany', 'tla' => 'GER'],
                    'awayTeam' => ['id' => 2, 'name' => 'Paraguay', 'tla' => 'PAR'],
                    'score' => [
                        'winner' => null,
                        'duration' => 'PENALTY_SHOOTOUT',
                        'fullTime' => ['home' => 4, 'away' => 5],
                        'regularTime' => ['home' => 1, 'away' => 1],
                        'extraTime' => ['home' => 0, 'away' => 0],
                        // football-data rapporteert hier een gelijke (foute)
                        // reeks; de echte stand leiden we af uit fullTime.
                        'penalties' => ['home' => 4, 'away' => 4],
                    ],
                ]],
            ]),
        ]);

        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $match = GameMatch::create([
            'tournament_id' => $tournament->id,
            'external_id' => 537415,
            'kickoff_at' => '2026-06-29 20:30:00',
            'status' => 'TIMED',
        ]);

        $exactUser = User::factory()->create();
        Prediction::create(['user_id' => $exactUser->id, 'match_id' => $match->id, 'home_score' => 1, 'away_score' => 1]);

        $missUser = User::factory()->create();
        Prediction::create(['user_id' => $missUser->id, 'match_id' => $match->id, 'home_score' => 4, 'away_score' => 5]);

        app(FootballDataSync::class)->syncResults($tournament);

        $match->refresh();
        $this->assertSame(1, $match->home_score);
        $this->assertSame(1, $match->away_score);
        $this->assertSame('PENALTIES', $match->decided_by);
        // Echte reeks afgeleid uit fullTime (4-5) − regularTime (1-1): 3-4.
        $this->assertSame(3, $match->penalty_home_score);
        $this->assertSame(4, $match->penalty_away_score);
        // De doorgestoten ploeg (uit) wint de strafschoppenreeks.
        $this->assertSame('AWAY_TEAM', $match->winner);
        $this->assertTrue($match->wentToShootout());
        $this->assertSame('n.s. 3-4', $match->resultSuffix());

        // 1-1 voorspeld = exact (5 punten); 4-5 voorspeld telt niet mee.
        $this->assertSame(5, Prediction::where('user_id', $exactUser->id)->first()->points);
        $this->assertSame(0, Prediction::where('user_id', $missUser->id)->first()->points);
    }

    public function test_extra_time_win_with_empty_regular_time_stores_the_90_minute_score(): void
    {
        // Belgium–Senegal (LAST_32): football-data laat regularTime leeg maar
        // verwerkt de verlengingsgoal in extraTime én fullTime, en labelt de
        // duur foutief als REGULAR. De stand na 90 min = fullTime − extraTime.
        Http::fake([
            'api.football-data.org/*' => Http::response([
                'matches' => [[
                    'id' => 537422,
                    'status' => 'FINISHED',
                    'utcDate' => '2026-07-01T20:00:00Z',
                    'stage' => 'LAST_32',
                    'homeTeam' => ['id' => 1, 'name' => 'Belgium', 'tla' => 'BEL'],
                    'awayTeam' => ['id' => 2, 'name' => 'Senegal', 'tla' => 'SEN'],
                    'score' => [
                        'winner' => 'HOME_TEAM',
                        'duration' => 'REGULAR',
                        'fullTime' => ['home' => 3, 'away' => 2],
                        'halfTime' => ['home' => 0, 'away' => 1],
                        'regularTime' => ['home' => null, 'away' => null],
                        'extraTime' => ['home' => 1, 'away' => 0],
                    ],
                ]],
            ]),
        ]);

        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $match = GameMatch::create([
            'tournament_id' => $tournament->id,
            'external_id' => 537422,
            'kickoff_at' => '2026-07-01 20:00:00',
            'status' => 'TIMED',
        ]);

        $exactUser = User::factory()->create();
        Prediction::create(['user_id' => $exactUser->id, 'match_id' => $match->id, 'home_score' => 2, 'away_score' => 2]);

        $missUser = User::factory()->create();
        Prediction::create(['user_id' => $missUser->id, 'match_id' => $match->id, 'home_score' => 3, 'away_score' => 2]);

        app(FootballDataSync::class)->syncResults($tournament);

        $match->refresh();
        // 90-minutenstand, niet de 3-2 na verlenging.
        $this->assertSame(2, $match->home_score);
        $this->assertSame(2, $match->away_score);
        $this->assertSame('EXTRA_TIME', $match->decided_by);
        // De doorgestoten ploeg blijft België (winnaar via fullTime).
        $this->assertSame('HOME_TEAM', $match->winner);
        $this->assertTrue($match->wentToExtraTime());
        $this->assertNull($match->penalty_home_score);
        // Eindstand na verlenging apart bewaard en getoond als suffix.
        $this->assertSame(3, $match->extra_time_home_score);
        $this->assertSame(2, $match->extra_time_away_score);
        $this->assertSame('n.v. 3-2', $match->resultSuffix());

        // 2-2 voorspeld = exact (5 punten). 3-2 (de ET-stand) is niet langer
        // exact: enkel het uitdoelpunt (2) klopt, goed voor 1 punt.
        $this->assertSame(5, Prediction::where('user_id', $exactUser->id)->first()->points);
        $this->assertSame(1, Prediction::where('user_id', $missUser->id)->first()->points);
    }

    public function test_a_degraded_penalty_payload_does_not_wipe_a_resolved_winner(): void
    {
        // football-data soms degradeert een beslist duel terug naar een gelijke
        // stand zonder winnaar (fullTime 4-4). Dat mag de al vastgestelde
        // doorgestoten ploeg + strafschoppenreeks niet wissen.
        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        GameMatch::create([
            'tournament_id' => $tournament->id,
            'external_id' => 537418,
            'kickoff_at' => now()->subDay(),
            'status' => 'FINISHED',
            'home_score' => 1,
            'away_score' => 1,
            'decided_by' => 'PENALTIES',
            'penalty_home_score' => 2,
            'penalty_away_score' => 3,
            'winner' => 'AWAY_TEAM',
            'points_awarded' => true,
        ]);

        Http::fake([
            'api.football-data.org/*' => Http::response([
                'matches' => [[
                    'id' => 537418,
                    'status' => 'FINISHED',
                    'utcDate' => '2026-06-30T01:00:00Z',
                    'stage' => 'LAST_16',
                    'homeTeam' => ['id' => 1, 'name' => 'Netherlands', 'tla' => 'NED'],
                    'awayTeam' => ['id' => 2, 'name' => 'Morocco', 'tla' => 'MAR'],
                    'score' => [
                        'winner' => null,
                        'duration' => 'PENALTY_SHOOTOUT',
                        'fullTime' => ['home' => 4, 'away' => 4],
                        'regularTime' => ['home' => 1, 'away' => 1],
                        'extraTime' => ['home' => 0, 'away' => 0],
                        'penalties' => ['home' => 3, 'away' => 3],
                    ],
                ]],
            ]),
        ]);

        app(FootballDataSync::class)->syncResults($tournament);

        $match = GameMatch::where('external_id', 537418)->first();
        $this->assertSame('AWAY_TEAM', $match->winner);
        $this->assertSame(2, $match->penalty_home_score);
        $this->assertSame(3, $match->penalty_away_score);
        $this->assertSame('n.s. 2-3', $match->resultSuffix());
    }

    public function test_finished_status_without_a_score_is_not_recorded_as_finished(): void
    {
        // football-data flags the opening match FINISHED before the score is in.
        Http::fake([
            'api.football-data.org/*' => Http::response([
                'matches' => [[
                    'id' => 537327,
                    'status' => 'FINISHED',
                    'utcDate' => '2026-06-11T19:00:00Z',
                    'stage' => 'GROUP_STAGE',
                    'homeTeam' => ['id' => 1, 'name' => 'Mexico', 'tla' => 'MEX'],
                    'awayTeam' => ['id' => 2, 'name' => 'South Africa', 'tla' => 'RSA'],
                    'score' => ['winner' => null, 'fullTime' => ['home' => null, 'away' => null]],
                ]],
            ]),
        ]);

        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);

        $result = app(FootballDataSync::class)->syncResults($tournament);

        $match = GameMatch::where('external_id', 537327)->first();
        $this->assertNotNull($match);
        $this->assertNotSame('FINISHED', $match->status->value);
        $this->assertNull($match->home_score);
        $this->assertNull($match->finished_at);
        $this->assertSame(0, $result['scored']);
    }

    public function test_a_scoreless_payload_does_not_wipe_an_existing_result(): void
    {
        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        GameMatch::create([
            'tournament_id' => $tournament->id,
            'external_id' => 100,
            'kickoff_at' => now()->subDay(),
            'status' => 'FINISHED',
            'home_score' => 2,
            'away_score' => 1,
            'winner' => 'HOME_TEAM',
        ]);

        // A later sync returns the same match but momentarily omits the score.
        Http::fake([
            'api.football-data.org/*' => Http::response([
                'matches' => [[
                    'id' => 100,
                    'status' => 'FINISHED',
                    'utcDate' => '2026-06-12T19:00:00Z',
                    'homeTeam' => ['id' => 1, 'name' => 'Nederland', 'tla' => 'NED'],
                    'awayTeam' => ['id' => 2, 'name' => 'Brazilië', 'tla' => 'BRA'],
                    'score' => ['winner' => null, 'fullTime' => ['home' => null, 'away' => null]],
                ]],
            ]),
        ]);

        app(FootballDataSync::class)->syncResults($tournament);

        $match = GameMatch::where('external_id', 100)->first();
        $this->assertSame(2, $match->home_score);
        $this->assertSame(1, $match->away_score);
        $this->assertSame('FINISHED', $match->status->value);
    }

    public function test_a_locked_result_is_not_overwritten_by_the_sync(): void
    {
        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        // An admin corrected the result by hand and locked it.
        GameMatch::create([
            'tournament_id' => $tournament->id,
            'external_id' => 100,
            'kickoff_at' => now()->subDay(),
            'status' => 'FINISHED',
            'home_score' => 2,
            'away_score' => 1,
            'winner' => 'HOME_TEAM',
            'result_locked' => true,
        ]);

        // The API still returns its (wrong) score for the same match.
        Http::fake([
            'api.football-data.org/*' => Http::response([
                'matches' => [[
                    'id' => 100,
                    'status' => 'FINISHED',
                    'utcDate' => '2026-06-12T19:00:00Z',
                    'homeTeam' => ['id' => 1, 'name' => 'Nederland', 'tla' => 'NED'],
                    'awayTeam' => ['id' => 2, 'name' => 'Brazilië', 'tla' => 'BRA'],
                    'score' => ['winner' => 'AWAY_TEAM', 'fullTime' => ['home' => 0, 'away' => 3]],
                ]],
            ]),
        ]);

        app(FootballDataSync::class)->syncResults($tournament);

        $match = GameMatch::where('external_id', 100)->first();
        $this->assertSame(2, $match->home_score);
        $this->assertSame(1, $match->away_score);
        $this->assertSame('HOME_TEAM', $match->winner);
        $this->assertSame('FINISHED', $match->status->value);
    }

    public function test_score_arriving_via_fixtures_is_still_awarded_by_results_sync(): void
    {
        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);

        // The score was populated by the fixtures sync (unfiltered list)…
        $match = GameMatch::create([
            'tournament_id' => $tournament->id,
            'external_id' => 537327,
            'kickoff_at' => now()->subHour(),
            'status' => 'FINISHED',
            'home_score' => 2,
            'away_score' => 0,
            'winner' => 'HOME_TEAM',
            'points_awarded' => false,
        ]);
        $user = User::factory()->create();
        Prediction::create(['user_id' => $user->id, 'match_id' => $match->id, 'home_score' => 2, 'away_score' => 0]);

        // …but the FINISHED filter doesn't return it this run.
        Http::fake([
            'api.football-data.org/*' => Http::response(['matches' => []]),
        ]);

        $result = app(FootballDataSync::class)->syncResults($tournament);

        $this->assertSame(1, $result['scored']);
        $this->assertTrue($match->refresh()->points_awarded);
        $this->assertSame(5, Prediction::where('user_id', $user->id)->first()->points);
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
