<?php

namespace Database\Seeders;

use App\Enums\BonusType;
use App\Enums\UserRole;
use App\Models\BonusPrediction;
use App\Models\GameMatch;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Scoring\ScoreCalculationService;
use Illuminate\Database\Seeder;

class DemoTournamentSeeder extends Seeder
{
    public function run(ScoreCalculationService $scoring): void
    {
        $tournament = Tournament::updateOrCreate(
            ['code' => 'WC', 'season' => '2026'],
            [
                'name' => 'FIFA WK 2026',
                'starts_at' => now()->addHours(36),
                'ends_at' => now()->addDays(40),
                'is_active' => true,
            ],
        );

        $teams = collect([
            ['name' => 'Nederland', 'tla' => 'NED'],
            ['name' => 'Brazilië', 'tla' => 'BRA'],
            ['name' => 'Argentinië', 'tla' => 'ARG'],
            ['name' => 'Frankrijk', 'tla' => 'FRA'],
            ['name' => 'Duitsland', 'tla' => 'GER'],
            ['name' => 'Spanje', 'tla' => 'ESP'],
            ['name' => 'Engeland', 'tla' => 'ENG'],
            ['name' => 'Portugal', 'tla' => 'POR'],
            ['name' => 'België', 'tla' => 'BEL'],
            ['name' => 'Kroatië', 'tla' => 'CRO'],
            ['name' => 'Mexico', 'tla' => 'MEX'],
            ['name' => 'Verenigde Staten', 'tla' => 'USA'],
        ])->mapWithKeys(fn ($t) => [
            $t['tla'] => Team::updateOrCreate(
                ['tournament_id' => $tournament->id, 'tla' => $t['tla']],
                ['name' => $t['name'], 'short_name' => $t['name']],
            ),
        ]);

        // Finished matches (already played) with results.
        $finished = [
            ['NED', 'MEX', 3, 1, -6],
            ['BRA', 'CRO', 1, 1, -5],
            ['ARG', 'USA', 2, 0, -4],
            ['FRA', 'BEL', 2, 1, -3],
            ['GER', 'POR', 0, 2, -2],
            ['ESP', 'ENG', 1, 0, -1],
        ];

        $finishedModels = [];
        foreach ($finished as $i => [$home, $away, $hs, $as, $daysAgo]) {
            $finishedModels[] = GameMatch::updateOrCreate(
                ['tournament_id' => $tournament->id, 'home_team_id' => $teams[$home]->id, 'away_team_id' => $teams[$away]->id, 'matchday' => 1],
                [
                    'stage' => 'GROUP_STAGE',
                    'group' => 'Groep '.chr(65 + ($i % 4)),
                    'kickoff_at' => now()->addDays($daysAgo),
                    'status' => 'FINISHED',
                    'home_score' => $hs,
                    'away_score' => $as,
                    'winner' => $hs > $as ? 'HOME_TEAM' : ($hs < $as ? 'AWAY_TEAM' : 'DRAW'),
                    'finished_at' => now()->addDays($daysAgo)->addHours(2),
                    'points_awarded' => false,
                ],
            );
        }

        // Upcoming matches, still open for predictions.
        $upcoming = [
            ['NED', 'GER', 1],
            ['BRA', 'ARG', 2],
            ['FRA', 'ESP', 2],
            ['ENG', 'POR', 3],
            ['BEL', 'CRO', 4],
            ['MEX', 'USA', 4],
            ['NED', 'BRA', 6],
            ['ARG', 'FRA', 7],
        ];

        foreach ($upcoming as $i => [$home, $away, $daysAhead]) {
            GameMatch::updateOrCreate(
                ['tournament_id' => $tournament->id, 'home_team_id' => $teams[$home]->id, 'away_team_id' => $teams[$away]->id, 'matchday' => 2],
                [
                    'stage' => 'GROUP_STAGE',
                    'group' => 'Groep '.chr(65 + ($i % 4)),
                    'kickoff_at' => now()->addDays($daysAhead)->setTime(20, 0),
                    'status' => 'TIMED',
                ],
            );
        }

        // Demo participants.
        $names = ['Sanne de Vries', 'Daan Jansen', 'Eva Bakker', 'Tom Visser', 'Lotte Smit', 'Bram Mulder', 'Femke Bos'];
        $participants = collect($names)->map(fn ($name, $i) => User::updateOrCreate(
            ['email' => 'deelnemer'.($i + 1).'@valicare.nl'],
            ['name' => $name, 'role' => UserRole::Deelnemer, 'password' => 'password', 'email_verified_at' => now()],
        ));

        $admin = User::where('email', 'lucien@valicare.nl')->first();
        if ($admin) {
            $participants->push($admin);
        }

        // Predictions on finished matches (varied so points spread out).
        foreach ($finishedModels as $mi => $match) {
            foreach ($participants->values() as $pi => $participant) {
                $offset = ($pi + $mi) % 4;
                $predHome = match ($offset) {
                    0 => $match->home_score,        // exact
                    1 => $match->home_score + 1,    // maybe outcome
                    2 => max(0, $match->home_score - 1),
                    default => $match->away_score,  // often wrong
                };
                $predAway = match ($offset) {
                    0 => $match->away_score,
                    1 => $match->away_score,
                    2 => $match->away_score + 1,
                    default => $match->home_score,
                };

                Prediction::updateOrCreate(
                    ['user_id' => $participant->id, 'match_id' => $match->id],
                    ['home_score' => $predHome, 'away_score' => $predAway],
                );
            }

            $scoring->awardMatch($match->fresh());
        }

        // A few predictions on the first upcoming match (kept secret until kickoff).
        $firstUpcoming = GameMatch::where('tournament_id', $tournament->id)->where('matchday', 2)->orderBy('kickoff_at')->first();
        if ($firstUpcoming) {
            foreach ($participants->take(4)->values() as $pi => $participant) {
                Prediction::updateOrCreate(
                    ['user_id' => $participant->id, 'match_id' => $firstUpcoming->id],
                    ['home_score' => 2, 'away_score' => $pi % 2],
                );
            }
        }

        // Bonus predictions (open while tournament hasn't started).
        $bonusTeams = ['BRA', 'FRA', 'ARG', 'NED', 'ESP', 'GER', 'POR'];
        foreach ($participants->values() as $pi => $participant) {
            BonusPrediction::updateOrCreate(
                ['user_id' => $participant->id, 'tournament_id' => $tournament->id, 'type' => BonusType::Winner->value],
                ['team_id' => $teams[$bonusTeams[$pi % count($bonusTeams)]]->id],
            );
            BonusPrediction::updateOrCreate(
                ['user_id' => $participant->id, 'tournament_id' => $tournament->id, 'type' => BonusType::Finalist->value],
                ['team_id' => $teams[$bonusTeams[($pi + 3) % count($bonusTeams)]]->id],
            );
            BonusPrediction::updateOrCreate(
                ['user_id' => $participant->id, 'tournament_id' => $tournament->id, 'type' => BonusType::TopScorer->value],
                ['player_name' => ['Mbappé', 'Haaland', 'Messi', 'Vinícius Jr', 'Kane'][$pi % 5]],
            );
        }
    }
}
