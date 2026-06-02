<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Odds\OddsApiSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OddsSyncTest extends TestCase
{
    use RefreshDatabase;

    private function event(string $home, string $away, float $h, float $d, float $a): array
    {
        $book = fn () => [
            'key' => 'pinnacle',
            'markets' => [[
                'key' => 'h2h',
                'outcomes' => [
                    ['name' => $home, 'price' => $h],
                    ['name' => $away, 'price' => $a],
                    ['name' => 'Draw', 'price' => $d],
                ],
            ]],
        ];

        return ['home_team' => $home, 'away_team' => $away, 'bookmakers' => [$book(), $book()]];
    }

    public function test_it_attaches_averaged_odds_to_the_matching_fixture(): void
    {
        Http::fake([
            'api.the-odds-api.com/*' => Http::response([
                $this->event('Mexico', 'South Africa', 1.40, 4.50, 8.00),
            ]),
        ]);

        $t = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $home = Team::create(['tournament_id' => $t->id, 'name' => 'Mexico']);
        $away = Team::create(['tournament_id' => $t->id, 'name' => 'South Africa']);
        $match = GameMatch::create([
            'tournament_id' => $t->id, 'home_team_id' => $home->id, 'away_team_id' => $away->id,
            'kickoff_at' => now()->addDays(5), 'status' => 'TIMED',
        ]);

        $result = app(OddsApiSync::class)->sync($t);

        $this->assertSame(['events' => 1, 'matched' => 1], $result);

        $match->refresh();
        $this->assertEqualsWithDelta(1.40, $match->odds['home'], 0.001);
        $this->assertEqualsWithDelta(4.50, $match->odds['draw'], 0.001);
        $this->assertEqualsWithDelta(8.00, $match->odds['away'], 0.001);
        $this->assertSame('HOME_TEAM', $match->favouriteOutcome());
    }

    public function test_team_name_aliases_are_reconciled(): void
    {
        Http::fake([
            'api.the-odds-api.com/*' => Http::response([
                // Odds API spells these differently than football-data.
                $this->event('Czech Republic', 'USA', 2.10, 3.30, 3.50),
            ]),
        ]);

        $t = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $home = Team::create(['tournament_id' => $t->id, 'name' => 'Czechia']);
        $away = Team::create(['tournament_id' => $t->id, 'name' => 'United States']);
        $match = GameMatch::create([
            'tournament_id' => $t->id, 'home_team_id' => $home->id, 'away_team_id' => $away->id,
            'kickoff_at' => now()->addDays(5), 'status' => 'TIMED',
        ]);

        $result = app(OddsApiSync::class)->sync($t);

        $this->assertSame(1, $result['matched']);
        $this->assertEqualsWithDelta(2.10, $match->refresh()->odds['home'], 0.001);
    }
}
