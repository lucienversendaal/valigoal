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
    /**
     * Demo timeline toggle.
     *
     * true  → de groepsfase is al begonnen. Je ziet het volledige overzicht
     *         "wie heeft wat voorspeld" per wedstrijd (ingekleurd op punten),
     *         de groepsklok staat op "gesloten" en de bonusklok telt live af
     *         naar de eerste knockoutwedstrijd.
     * false → de groepsfase moet nog beginnen. De groepsklok telt live af,
     *         de invulvelden staan open en alle voorspellingen blijven geheim
     *         tot de deadline. De bonusklok telt eveneens live af.
     */
    private bool $groupStarted = true;

    public function run(ScoreCalculationService $scoring): void
    {
        // Kickoff van de allereerste groepswedstrijd = de sluiting van de hele
        // groepsfase. Alle andere groepswedstrijden liggen hierna.
        $anchor = $this->groupStarted
            ? now()->subDays(2)->setTime(18, 0)
            : now()->addHours(20)->startOfHour();

        $tournament = Tournament::updateOrCreate(
            ['code' => 'WC', 'season' => '2026'],
            [
                'name' => 'FIFA WK 2026',
                'starts_at' => $anchor,
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
        $participants = $participants->values();

        // Group fixtures: [home, away, group, +uren na anchor, thuis, uit].
        // De uitslag wordt alleen gebruikt als de wedstrijd in het verleden ligt.
        $groupFixtures = [
            // Speelronde 1 — rond de anchor (eerste wedstrijd = de deadline).
            ['NED', 'MEX', 'A', 0, 3, 1],
            ['BRA', 'CRO', 'B', 3, 1, 1],
            ['ARG', 'USA', 'C', 24, 2, 0],
            ['FRA', 'BEL', 'D', 27, 2, 1],
            ['GER', 'POR', 'A', 30, 0, 2],
            ['ESP', 'ENG', 'B', 48, 1, 0],
            // Speelronde 2 — enkele dagen later, nog steeds groepsfase.
            ['NED', 'GER', 'A', 96, 2, 2],
            ['BRA', 'ARG', 'B', 99, 1, 0],
            ['FRA', 'ESP', 'D', 102, 1, 1],
            ['ENG', 'POR', 'B', 120, 0, 0],
            ['BEL', 'CRO', 'D', 123, 2, 1],
            ['MEX', 'USA', 'C', 126, 0, 3],
        ];

        foreach ($groupFixtures as $i => [$home, $away, $group, $hours, $hs, $as]) {
            $kickoff = $anchor->copy()->addHours($hours);
            $matchday = $i < 6 ? 1 : 2;
            $finished = $kickoff->isPast();

            $match = GameMatch::updateOrCreate(
                ['tournament_id' => $tournament->id, 'home_team_id' => $teams[$home]->id, 'away_team_id' => $teams[$away]->id, 'matchday' => $matchday],
                [
                    'stage' => 'GROUP_STAGE',
                    'group' => 'Groep '.$group,
                    'kickoff_at' => $kickoff,
                    'status' => $finished ? 'FINISHED' : 'TIMED',
                    'home_score' => $finished ? $hs : null,
                    'away_score' => $finished ? $as : null,
                    'winner' => $finished ? ($hs > $as ? 'HOME_TEAM' : ($hs < $as ? 'AWAY_TEAM' : 'DRAW')) : null,
                    'finished_at' => $finished ? $kickoff->copy()->addHours(2) : null,
                    'points_awarded' => false,
                ],
            );

            // Iedereen voorspelt elke groepswedstrijd. Op gespeelde wedstrijden
            // verdelen we de voorspellingen over vier archetypes zodat elke
            // wedstrijd alle puntenwaarden laat zien: 5 (exact), 4 (juiste
            // uitkomst + één team exact goed), 3 (juiste uitkomst) en 0 (mis).
            foreach ($participants as $pi => $participant) {
                [$predHome, $predAway] = $finished
                    ? $this->craftPrediction(($pi + $i) % 4, $hs, $as)
                    : [($pi + $i) % 3, ($pi * 2 + $i) % 3];

                Prediction::updateOrCreate(
                    ['user_id' => $participant->id, 'match_id' => $match->id],
                    ['home_score' => $predHome, 'away_score' => $predAway],
                );
            }

            if ($finished) {
                $scoring->awardMatch($match->fresh());
            }
        }

        // Knockoutwedstrijden — altijd in de toekomst, teams nog te loten.
        // De vroegste hiervan bepaalt de sluiting van de bonusvragen.
        $knockout = [
            ['LAST_16', 8, 10],
            ['QUARTER_FINALS', 11, 11],
            ['SEMI_FINALS', 14, 12],
            ['FINAL', 17, 13],
        ];

        foreach ($knockout as [$stage, $daysAhead, $matchday]) {
            GameMatch::updateOrCreate(
                ['tournament_id' => $tournament->id, 'stage' => $stage, 'matchday' => $matchday],
                [
                    'home_team_id' => null,
                    'away_team_id' => null,
                    'group' => null,
                    'kickoff_at' => now()->addDays($daysAhead)->setTime(20, 0),
                    'status' => 'TIMED',
                ],
            );
        }

        // Bonusvoorspellingen — open tot de eerste knockoutwedstrijd.
        // Winnaar en finalist zijn bewust altijd verschillende landen.
        $bonusTeams = ['BRA', 'FRA', 'ARG', 'NED', 'ESP', 'GER', 'POR'];
        foreach ($participants as $pi => $participant) {
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

    /**
     * Bouw een voorspelling die een bepaalde puntenwaarde oplevert tegen de
     * uitslag $hs–$as, volgens de actieve scoringsregel:
     *   0 → exacte uitslag (5)
     *   1 → juiste uitkomst + één team exact goed (4); bij gelijkspel niet
     *       mogelijk, dan valt het terug op 3
     *   2 → juiste uitkomst, geen team exact goed (3)
     *   3 → verkeerde uitkomst (0)
     *
     * @return array{0: int, 1: int} [thuisscore, uitscore]
     */
    private function craftPrediction(int $archetype, int $hs, int $as): array
    {
        return match ($archetype) {
            0 => [$hs, $as],
            1 => match (true) {
                $hs > $as => [$hs + 1, $as], // thuiswinst: uitploeg exact goed
                $hs < $as => [$hs, $as + 1], // uitwinst: thuisploeg exact goed
                default => [$hs + 1, $as + 1], // gelijkspel: +1 niet mogelijk
            },
            2 => [$hs + 1, $as + 1],
            default => [$as, $hs + 1],
        };
    }
}
