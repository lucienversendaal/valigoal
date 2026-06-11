<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\KnockoutRoundOpen;
use App\Notifications\KnockoutRoundReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class KnockoutRoundMailTest extends TestCase
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

    private function playedGroupMatch(string $kickoff): GameMatch
    {
        return GameMatch::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'stage' => 'GROUP_STAGE',
            'kickoff_at' => $kickoff,
            'status' => 'FINISHED',
            'home_score' => 1,
            'away_score' => 0,
        ]);
    }

    private function last32Match(string $kickoff): GameMatch
    {
        return GameMatch::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'stage' => 'LAST_32',
            'kickoff_at' => $kickoff,
            'status' => 'TIMED',
        ]);
    }

    public function test_round_open_is_announced_once_when_the_previous_round_is_played(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->playedGroupMatch(now()->subDay()->toDateTimeString());
        // Deadline well beyond the reminder window so only the announcement fires.
        $this->last32Match(now()->addDays(3)->toDateTimeString());

        $this->artisan('predictions:remind')->assertSuccessful();
        $this->artisan('predictions:remind')->assertSuccessful(); // second run must not re-announce

        Notification::assertSentToTimes($user, KnockoutRoundOpen::class, 1);
        Notification::assertNotSentTo($user, KnockoutRoundReminder::class);
    }

    public function test_round_is_not_announced_while_the_previous_round_is_unfinished(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        // Group match still scheduled — round not played yet.
        GameMatch::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'stage' => 'GROUP_STAGE',
            'kickoff_at' => now()->addHour()->toDateTimeString(),
            'status' => 'TIMED',
        ]);
        $this->last32Match(now()->addDays(3)->toDateTimeString());

        $this->artisan('predictions:remind')->assertSuccessful();

        Notification::assertNotSentTo($user, KnockoutRoundOpen::class);
    }

    public function test_reminder_goes_to_users_with_open_matches_only(): void
    {
        Notification::fake();

        $behind = User::factory()->create();
        $done = User::factory()->create();

        $this->playedGroupMatch(now()->subDay()->toDateTimeString());
        // Deadline within the 24h lead window.
        $match = $this->last32Match(now()->addHours(5)->toDateTimeString());

        Prediction::create([
            'user_id' => $done->id,
            'match_id' => $match->id,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        $this->artisan('predictions:remind')->assertSuccessful();

        Notification::assertSentTo($behind, KnockoutRoundReminder::class);
        Notification::assertNotSentTo($done, KnockoutRoundReminder::class);
    }
}
