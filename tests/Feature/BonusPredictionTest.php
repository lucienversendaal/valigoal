<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BonusPredictionTest extends TestCase
{
    use RefreshDatabase;

    private Tournament $tournament;

    private Team $france;

    private Team $brazil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $this->france = Team::create(['tournament_id' => $this->tournament->id, 'name' => 'Frankrijk']);
        $this->brazil = Team::create(['tournament_id' => $this->tournament->id, 'name' => 'Brazilië']);

        // Group phase still ahead, no knockout fixtures yet → bonuses are open.
        GameMatch::create([
            'tournament_id' => $this->tournament->id,
            'stage' => 'GROUP_STAGE',
            'kickoff_at' => now()->addDays(3),
            'status' => 'TIMED',
        ]);
    }

    public function test_winner_and_finalist_cannot_be_the_same_country(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::bonus')
            ->set('winnerTeamId', $this->france->id)
            ->set('finalistTeamId', $this->france->id)
            ->call('save')
            ->assertHasErrors(['finalistTeamId' => 'different']);

        $this->assertDatabaseCount('bonus_predictions', 0);
    }

    public function test_different_countries_are_accepted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::bonus')
            ->set('winnerTeamId', $this->france->id)
            ->set('finalistTeamId', $this->brazil->id)
            ->set('topScorer', 'Mbappé')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bonus_predictions', [
            'user_id' => $user->id,
            'type' => 'winner',
            'team_id' => $this->france->id,
        ]);
        $this->assertDatabaseHas('bonus_predictions', [
            'user_id' => $user->id,
            'type' => 'finalist',
            'team_id' => $this->brazil->id,
        ]);
    }

    public function test_bonus_save_is_blocked_once_knockout_has_started(): void
    {
        GameMatch::create([
            'tournament_id' => $this->tournament->id,
            'stage' => 'LAST_16',
            'kickoff_at' => now()->subMinute(),
            'status' => 'TIMED',
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::bonus')
            ->set('winnerTeamId', $this->france->id)
            ->set('finalistTeamId', $this->brazil->id)
            ->call('save');

        $this->assertDatabaseCount('bonus_predictions', 0);
    }
}
