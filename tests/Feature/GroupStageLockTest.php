<?php

namespace Tests\Feature;

use App\Exceptions\PredictionLockedException;
use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Predictions\PredictionLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupStageLockTest extends TestCase
{
    use RefreshDatabase;

    private Tournament $tournament;

    private Team $home;

    private Team $away;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $this->home = Team::create(['tournament_id' => $this->tournament->id, 'name' => 'NED']);
        $this->away = Team::create(['tournament_id' => $this->tournament->id, 'name' => 'BRA']);
    }

    private function match(string $stage, string $kickoff): GameMatch
    {
        return GameMatch::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'stage' => $stage,
            'kickoff_at' => $kickoff,
            'status' => 'TIMED',
        ]);
    }

    public function test_group_deadline_is_the_first_group_kickoff(): void
    {
        $this->match('GROUP_STAGE', now()->addDays(2)->toDateTimeString());
        $first = $this->match('GROUP_STAGE', now()->addHour()->toDateTimeString());

        $this->assertEquals(
            $first->kickoff_at->timestamp,
            $this->tournament->groupStageLocksAt()->timestamp,
        );
    }

    public function test_a_later_group_match_stays_open_until_the_first_group_kickoff(): void
    {
        // First group match kicks off in an hour; this one is days later.
        $this->match('GROUP_STAGE', now()->addHour()->toDateTimeString());
        $later = $this->match('GROUP_STAGE', now()->addDays(3)->toDateTimeString());

        $this->assertTrue(app(PredictionLockService::class)->canEdit($later));
    }

    public function test_all_group_matches_lock_once_the_first_kickoff_has_passed(): void
    {
        // The first group match already kicked off.
        $this->match('GROUP_STAGE', now()->subMinute()->toDateTimeString());
        $later = $this->match('GROUP_STAGE', now()->addDays(3)->toDateTimeString());

        $service = app(PredictionLockService::class);

        $this->assertFalse($service->canEdit($later));
        $this->assertTrue($service->predictionsAreVisible($later));

        $this->expectException(PredictionLockedException::class);
        $service->save(User::factory()->create(), $later, 1, 0);
    }

    public function test_group_predictions_are_secret_until_the_group_deadline(): void
    {
        $this->match('GROUP_STAGE', now()->addHour()->toDateTimeString());
        $match = $this->match('GROUP_STAGE', now()->addDays(2)->toDateTimeString());

        $this->assertFalse(app(PredictionLockService::class)->predictionsAreVisible($match));
    }

    public function test_knockout_match_still_locks_at_its_own_kickoff(): void
    {
        // Group phase has not started yet, but this knockout match kicked off.
        $this->match('GROUP_STAGE', now()->addDays(5)->toDateTimeString());
        $knockout = $this->match('LAST_16', now()->subMinute()->toDateTimeString());

        $service = app(PredictionLockService::class);

        $this->assertFalse($service->canEdit($knockout));
        $this->assertTrue($service->predictionsAreVisible($knockout));
    }

    public function test_knockout_start_and_bonus_lock(): void
    {
        $this->match('GROUP_STAGE', now()->addDays(5)->toDateTimeString());
        $firstKnockout = $this->match('LAST_16', now()->subMinute()->toDateTimeString());
        $this->match('FINAL', now()->addDays(10)->toDateTimeString());

        $this->assertEquals(
            $firstKnockout->kickoff_at->timestamp,
            $this->tournament->knockoutStartsAt()->timestamp,
        );
        $this->assertTrue($this->tournament->bonusesAreLocked());
    }

    public function test_bonus_stays_open_while_knockout_is_in_the_future(): void
    {
        $this->match('GROUP_STAGE', now()->subMinute()->toDateTimeString());
        $this->match('LAST_16', now()->addDays(7)->toDateTimeString());

        $this->assertFalse($this->tournament->bonusesAreLocked());
    }

    public function test_bonus_stays_open_when_no_knockout_fixtures_exist_yet(): void
    {
        $this->match('GROUP_STAGE', now()->subMinute()->toDateTimeString());

        $this->assertNull($this->tournament->knockoutStartsAt());
        $this->assertFalse($this->tournament->bonusesAreLocked());
    }
}
