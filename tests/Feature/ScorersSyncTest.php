<?php

namespace Tests\Feature;

use App\Models\HistoricalFact;
use App\Models\Tournament;
use App\Services\FootballData\FootballDataSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScorersSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_live_top_scorers_for_the_tournament_year(): void
    {
        Http::fake([
            'api.football-data.org/*' => Http::response([
                'scorers' => [
                    ['player' => ['name' => 'Kylian Mbappé'], 'team' => ['name' => 'France'], 'goals' => 5],
                    ['player' => ['name' => 'Harry Kane'], 'team' => ['name' => 'England'], 'goals' => 4],
                ],
            ]),
        ]);

        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'season' => '2026', 'is_active' => true]);

        $count = app(FootballDataSync::class)->syncScorers($tournament);

        $this->assertSame(2, $count);

        $rows = HistoricalFact::where('type', 'wc_top_scorer')->where('year', 2026)->orderBy('sort_order')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('Kylian Mbappé', $rows->first()->title);
        $this->assertSame(5, (int) data_get($rows->first()->meta, 'goals'));
    }

    public function test_resyncing_replaces_previous_live_scorers(): void
    {
        Http::fake([
            'api.football-data.org/*' => Http::sequence()
                ->push(['scorers' => [
                    ['player' => ['name' => 'Player A'], 'team' => ['name' => 'X'], 'goals' => 2],
                    ['player' => ['name' => 'Player B'], 'team' => ['name' => 'Y'], 'goals' => 1],
                ]])
                ->push(['scorers' => [
                    ['player' => ['name' => 'Player A'], 'team' => ['name' => 'X'], 'goals' => 3],
                ]]),
        ]);

        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'season' => '2026', 'is_active' => true]);
        $sync = app(FootballDataSync::class);

        $sync->syncScorers($tournament);
        $sync->syncScorers($tournament);

        $rows = HistoricalFact::where('type', 'wc_top_scorer')->where('year', 2026)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(3, (int) data_get($rows->first()->meta, 'goals'));
    }
}
