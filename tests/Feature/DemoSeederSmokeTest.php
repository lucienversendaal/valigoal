<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Scoring\ScoreCalculationService;
use Database\Seeders\DemoTournamentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_runs_and_produces_a_coherent_tournament(): void
    {
        $this->seed(DemoTournamentSeeder::class);

        $tournament = Tournament::current();
        $this->assertNotNull($tournament);

        // Group deadline = first group kickoff, knockout deadline later.
        $this->assertNotNull($tournament->groupStageLocksAt());
        $this->assertNotNull($tournament->knockoutStartsAt());
        $this->assertTrue($tournament->knockoutStartsAt()->greaterThan($tournament->groupStageLocksAt()));

        // Bonus open while knockout is still in the future.
        $this->assertFalse($tournament->bonusesAreLocked());

        // Finished group matches exist and everyone predicted every group match.
        $this->assertGreaterThan(0, GameMatch::where('status', 'FINISHED')->count());
        $groupMatches = GameMatch::where('stage', 'GROUP_STAGE')->count();
        $participants = User::where('role', UserRole::Deelnemer)->count();
        $this->assertDatabaseCount('predictions', $groupMatches * $participants);

        // Knockout fixtures are scheduled with teams still to be drawn.
        $this->assertGreaterThan(0, GameMatch::whereNull('home_team_id')->whereNotNull('stage')->where('stage', '!=', 'GROUP_STAGE')->count());
    }

    public function test_pre_tournament_toggle_keeps_the_group_stage_open(): void
    {
        $seeder = new DemoTournamentSeeder;
        $property = new \ReflectionProperty($seeder, 'groupStarted');
        $property->setValue($seeder, false);

        $seeder->run(app(ScoreCalculationService::class));

        $tournament = Tournament::current();

        // Group stage still ahead → live countdown, nothing locked or finished.
        $this->assertTrue($tournament->groupStageLocksAt()->isFuture());
        $this->assertFalse($tournament->groupStageIsLocked());
        $this->assertSame(0, GameMatch::where('status', 'FINISHED')->count());
    }
}
