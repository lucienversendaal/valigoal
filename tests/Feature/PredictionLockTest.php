<?php

namespace Tests\Feature;

use App\Exceptions\PredictionLockedException;
use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\PredictionAuditLog;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Predictions\PredictionLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionLockTest extends TestCase
{
    use RefreshDatabase;

    private function match(string $kickoff): GameMatch
    {
        $tournament = Tournament::create(['name' => 'WK', 'code' => 'WC', 'is_active' => true]);
        $home = Team::create(['tournament_id' => $tournament->id, 'name' => 'NED']);
        $away = Team::create(['tournament_id' => $tournament->id, 'name' => 'BRA']);

        return GameMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'kickoff_at' => $kickoff,
            'status' => 'TIMED',
        ]);
    }

    public function test_prediction_can_be_saved_before_kickoff_and_is_audited(): void
    {
        $user = User::factory()->create();
        $match = $this->match(now()->addHour()->toDateTimeString());

        $service = app(PredictionLockService::class);
        $this->assertTrue($service->canEdit($match));

        $service->save($user, $match, 3, 1);

        $this->assertDatabaseHas('predictions', [
            'user_id' => $user->id,
            'match_id' => $match->id,
            'home_score' => 3,
            'away_score' => 1,
        ]);
        $this->assertDatabaseHas('prediction_audit_logs', [
            'user_id' => $user->id,
            'match_id' => $match->id,
            'action' => 'created',
        ]);
    }

    public function test_updating_a_prediction_logs_an_update(): void
    {
        $user = User::factory()->create();
        $match = $this->match(now()->addHour()->toDateTimeString());
        $service = app(PredictionLockService::class);

        $service->save($user, $match, 1, 0);
        $service->save($user, $match, 2, 2);

        $this->assertSame(1, Prediction::where('user_id', $user->id)->count());
        $this->assertSame(1, PredictionAuditLog::where('action', 'updated')->count());
    }

    public function test_prediction_is_blocked_after_kickoff(): void
    {
        $user = User::factory()->create();
        $match = $this->match(now()->subMinute()->toDateTimeString());
        $service = app(PredictionLockService::class);

        $this->assertFalse($service->canEdit($match));

        $this->expectException(PredictionLockedException::class);
        $service->save($user, $match, 1, 0);
    }

    public function test_predictions_are_secret_until_kickoff(): void
    {
        $service = app(PredictionLockService::class);

        $open = $this->match(now()->addHour()->toDateTimeString());
        $locked = $this->match(now()->subHour()->toDateTimeString());

        $this->assertFalse($service->predictionsAreVisible($open));
        $this->assertTrue($service->predictionsAreVisible($locked));
    }
}
