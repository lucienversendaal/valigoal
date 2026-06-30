<?php

namespace App\Services\FootballData;

use App\Models\GameMatch;
use App\Models\HistoricalFact;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Scoring\ScoreCalculationService;
use Illuminate\Support\Carbon;

class FootballDataSync
{
    public function __construct(
        protected FootballDataClient $client,
        protected ScoreCalculationService $scoring,
    ) {}

    /**
     * Sync competition meta + teams + the full fixture list.
     *
     * @return array{tournament: Tournament, teams: int, matches: int}
     */
    public function syncAll(): array
    {
        $tournament = $this->syncCompetition();
        $teams = $this->syncTeams($tournament);
        $matches = $this->syncMatches($tournament);
        $standings = $this->syncStandings($tournament);

        return ['tournament' => $tournament, 'teams' => $teams, 'matches' => $matches, 'standings' => $standings];
    }

    public function syncCompetition(): Tournament
    {
        $data = $this->client->competition();
        $season = $data['currentSeason'] ?? [];

        $tournament = Tournament::updateOrCreate(
            ['external_id' => $data['id'] ?? null],
            [
                'code' => $data['code'] ?? $this->client->competitionCode(),
                'name' => $data['name'] ?? 'Wereldkampioenschap',
                'season' => isset($season['startDate']) ? substr((string) $season['startDate'], 0, 4) : null,
                'emblem_url' => $data['emblem'] ?? null,
                'starts_at' => isset($season['startDate']) ? Carbon::parse($season['startDate']) : null,
                'ends_at' => isset($season['endDate']) ? Carbon::parse($season['endDate']) : null,
                'is_active' => true,
            ],
        );

        // Keep a single active tournament so the app always points at live data.
        Tournament::where('id', '!=', $tournament->id)->update(['is_active' => false]);

        return $tournament;
    }

    public function syncStandings(Tournament $tournament): int
    {
        $groups = $this->client->standings()['standings'] ?? [];
        $count = 0;

        foreach ($groups as $group) {
            if (($group['type'] ?? 'TOTAL') !== 'TOTAL') {
                continue;
            }

            foreach ($group['table'] ?? [] as $row) {
                $teamId = $this->resolveTeamId($tournament, $row['team'] ?? []);

                if (! $teamId) {
                    continue;
                }

                Standing::updateOrCreate(
                    ['tournament_id' => $tournament->id, 'group' => $group['group'] ?? null, 'team_id' => $teamId],
                    [
                        'position' => $row['position'] ?? 0,
                        'played' => $row['playedGames'] ?? 0,
                        'won' => $row['won'] ?? 0,
                        'draw' => $row['draw'] ?? 0,
                        'lost' => $row['lost'] ?? 0,
                        'goals_for' => $row['goalsFor'] ?? 0,
                        'goals_against' => $row['goalsAgainst'] ?? 0,
                        'goal_difference' => $row['goalDifference'] ?? 0,
                        'points' => $row['points'] ?? 0,
                    ],
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Pull the live top scorers for the tournament and store them as
     * `wc_top_scorer` facts for the tournament's season year.
     */
    public function syncScorers(Tournament $tournament): int
    {
        $year = (int) ($tournament->season ?: ($tournament->starts_at?->year ?? now()->year));
        $scorers = $this->client->scorers(15)['scorers'] ?? [];

        HistoricalFact::where('type', 'wc_top_scorer')->where('year', $year)->delete();

        foreach ($scorers as $i => $scorer) {
            HistoricalFact::create([
                'type' => 'wc_top_scorer',
                'year' => $year,
                'title' => $scorer['player']['name'] ?? 'Onbekend',
                'subtitle' => $scorer['team']['name'] ?? null,
                'meta' => ['goals' => $scorer['goals'] ?? 0, 'live' => true],
                'sort_order' => $i,
            ]);
        }

        return count($scorers);
    }

    public function syncTeams(Tournament $tournament): int
    {
        $teams = $this->client->teams()['teams'] ?? [];

        foreach ($teams as $team) {
            Team::updateOrCreate(
                ['tournament_id' => $tournament->id, 'external_id' => $team['id']],
                [
                    'name' => $team['name'] ?? 'Onbekend',
                    'short_name' => $team['shortName'] ?? null,
                    'tla' => $team['tla'] ?? null,
                    'crest_url' => $team['crest'] ?? null,
                ],
            );
        }

        return count($teams);
    }

    public function syncMatches(Tournament $tournament): int
    {
        $matches = $this->client->matches()['matches'] ?? [];

        foreach ($matches as $match) {
            $this->upsertMatch($tournament, $match);
        }

        return count($matches);
    }

    /**
     * Pull finished matches, store results and award points for newly final games.
     *
     * @return array{updated: int, scored: int}
     */
    public function syncResults(Tournament $tournament): array
    {
        $matches = $this->client->matches(['status' => 'FINISHED'])['matches'] ?? [];

        foreach ($matches as $match) {
            $this->upsertMatch($tournament, $match);
        }

        // Score every finished match that still owes points, regardless of which
        // sync populated the result. football-data's FINISHED filter is flaky, so
        // a score can arrive via the fixtures sync without this endpoint listing it.
        $scored = $this->awardPendingMatches($tournament);

        return ['updated' => count($matches), 'scored' => $scored];
    }

    /**
     * Award points to any finished match of the tournament that hasn't been
     * scored yet.
     *
     * @return int Number of matches scored.
     */
    public function awardPendingMatches(Tournament $tournament): int
    {
        $scored = 0;

        $tournament->matches()
            ->where('points_awarded', false)
            ->get()
            ->each(function (GameMatch $match) use (&$scored) {
                if ($match->hasResult()) {
                    $this->scoring->awardMatch($match);
                    $scored++;
                }
            });

        return $scored;
    }

    /**
     * Resolve the final's winner and, once both the winner and the top scorer
     * are known, award the tournament bonus points. Returns the number of
     * correct bonuses, or null when the outcome isn't complete yet.
     */
    public function finalizeBonuses(Tournament $tournament): ?int
    {
        $this->scoring->resolveFinalOutcome($tournament);

        if (! $tournament->winner_team_id || ! $tournament->top_scorer_name) {
            return null;
        }

        return $this->scoring->awardBonuses($tournament);
    }

    /**
     * @param  array<string, mixed>  $match
     */
    protected function upsertMatch(Tournament $tournament, array $match): GameMatch
    {
        $homeId = $this->resolveTeamId($tournament, $match['homeTeam'] ?? []);
        $awayId = $this->resolveTeamId($tournament, $match['awayTeam'] ?? []);
        $existing = GameMatch::where('external_id', $match['id'])->first();

        $score = $match['score'] ?? [];
        $fullTime = $score['fullTime'] ?? [];
        $regularTime = $score['regularTime'] ?? [];
        $extraTime = $score['extraTime'] ?? [];

        // Bij verlenging/strafschoppen telt football-data de extra goals én de
        // strafschoppen mee in fullTime (1-1 wordt zo 4-5). De stand na 90
        // minuten staat in regularTime; dáártegen rekenen we voorspellingen af.
        $regulation = isset($regularTime['home'], $regularTime['away']) ? $regularTime : $fullTime;
        $incomingHasScore = isset($regulation['home'], $regulation['away']);

        // football-data occasionally returns a match without its score
        // (e.g. it flags the opening game as FINISHED before publishing the result).
        // Keep any score we already stored rather than wiping it back to null.
        $homeScore = $incomingHasScore ? $regulation['home'] : $existing?->home_score;
        $awayScore = $incomingHasScore ? $regulation['away'] : $existing?->away_score;

        // `winner` houdt de doorgestoten ploeg vast (na verlenging/strafschoppen)
        // zodat het knockoutschema en de finale-bonus blijven kloppen, ook al
        // staat de 90-minutenstand gelijk.
        $winner = $incomingHasScore ? $this->advancingTeam($regulation, $fullTime) : $existing?->winner;

        $decidedBy = $incomingHasScore
            ? $this->mapDuration($score['duration'] ?? null)
            : $existing?->decided_by;

        // Het `penalties`-veld van football-data is onbetrouwbaar (rapporteert
        // gelijke standen als 3-3 terwijl er een winnaar is). fullTime bevat de
        // strafschoppen bovenop de stand na 90 min + verlenging, dus leiden we
        // de echte reeks af uit het verschil.
        [$penaltyHome, $penaltyAway] = $incomingHasScore
            ? ($decidedBy === 'PENALTIES'
                ? $this->shootoutScore($fullTime, $regularTime, $extraTime)
                : [null, null])
            : [$existing?->penalty_home_score, $existing?->penalty_away_score];

        // A knockout tie always has a winner. football-data sometimes degrades a
        // decided result back to all-square (e.g. fullTime 4-4, no winner), which
        // would wipe a winner we already resolved. When the fresh payload can't
        // name a winner but we already had one, keep the stored decision.
        $isKnockoutDecider = in_array($decidedBy, ['EXTRA_TIME', 'PENALTIES'], true);

        if ($incomingHasScore && $isKnockoutDecider && $winner === null && $existing?->winner !== null) {
            $winner = $existing->winner;
            $decidedBy = $existing->decided_by ?? $decidedBy;
            $penaltyHome = $existing->penalty_home_score;
            $penaltyAway = $existing->penalty_away_score;
        }

        $status = $match['status'] ?? 'SCHEDULED';

        // Don't record a match as FINISHED until we actually have a result, so it
        // isn't shown as played (and skipped by scoring) without a score.
        if ($status === 'FINISHED' && ($homeScore === null || $awayScore === null)) {
            $status = 'TIMED';
        }

        // A result_locked match was corrected by hand in the admin. The API's
        // (wrong) score must not clobber it, so freeze the result fields and keep
        // whatever an admin stored.
        if ($existing?->result_locked) {
            $homeScore = $existing->home_score;
            $awayScore = $existing->away_score;
            $winner = $existing->winner;
            $decidedBy = $existing->decided_by;
            $penaltyHome = $existing->penalty_home_score;
            $penaltyAway = $existing->penalty_away_score;
            $status = $existing->status->value;
        }

        return GameMatch::updateOrCreate(
            ['external_id' => $match['id']],
            [
                'tournament_id' => $tournament->id,
                'home_team_id' => $homeId,
                'away_team_id' => $awayId,
                'stage' => $match['stage'] ?? null,
                'group' => $match['group'] ?? null,
                'matchday' => $match['matchday'] ?? null,
                'kickoff_at' => isset($match['utcDate']) ? Carbon::parse($match['utcDate']) : null,
                'status' => $status,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'winner' => $winner,
                'decided_by' => $decidedBy,
                'penalty_home_score' => $penaltyHome,
                'penalty_away_score' => $penaltyAway,
                'finished_at' => $existing?->result_locked
                    ? $existing->finished_at
                    : ($status === 'FINISHED' ? ($existing?->finished_at ?? now()) : null),
                'last_synced_at' => now(),
            ],
        );
    }

    /**
     * The team that progressed. fullTime already includes extra time and the
     * shootout, so it points at the winner of a knockout tie; fall back to the
     * regulation score otherwise. Returns null for a genuine regulation draw
     * (group stage).
     *
     * @param  array<string, int|null>  $regulation
     * @param  array<string, int|null>  $fullTime
     */
    protected function advancingTeam(array $regulation, array $fullTime): ?string
    {
        $decider = isset($fullTime['home'], $fullTime['away']) ? $fullTime : $regulation;

        if (! isset($decider['home'], $decider['away'])) {
            return null;
        }

        return match (true) {
            $decider['home'] > $decider['away'] => 'HOME_TEAM',
            $decider['home'] < $decider['away'] => 'AWAY_TEAM',
            default => null,
        };
    }

    /**
     * Reconstruct the real shootout tally from the score deltas, because
     * football-data's own `penalties` field reports tied scores. fullTime
     * carries regulation + extra time + penalties, so subtract the first two.
     *
     * @param  array<string, int|null>  $fullTime
     * @param  array<string, int|null>  $regularTime
     * @param  array<string, int|null>  $extraTime
     * @return array{0: int|null, 1: int|null}
     */
    protected function shootoutScore(array $fullTime, array $regularTime, array $extraTime): array
    {
        if (! isset($fullTime['home'], $fullTime['away'], $regularTime['home'], $regularTime['away'])) {
            return [null, null];
        }

        $extraHome = $extraTime['home'] ?? 0;
        $extraAway = $extraTime['away'] ?? 0;

        return [
            $fullTime['home'] - $regularTime['home'] - $extraHome,
            $fullTime['away'] - $regularTime['away'] - $extraAway,
        ];
    }

    /**
     * Normalise football-data's `duration` to the value we store.
     */
    protected function mapDuration(?string $duration): ?string
    {
        return match ($duration) {
            'PENALTY_SHOOTOUT', 'PENALTIES' => 'PENALTIES',
            'EXTRA_TIME' => 'EXTRA_TIME',
            'REGULAR' => 'REGULAR',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $team
     */
    protected function resolveTeamId(Tournament $tournament, array $team): ?int
    {
        if (empty($team['id'])) {
            return null;
        }

        return Team::updateOrCreate(
            ['tournament_id' => $tournament->id, 'external_id' => $team['id']],
            array_filter([
                'name' => $team['name'] ?? null,
                'short_name' => $team['shortName'] ?? null,
                'tla' => $team['tla'] ?? null,
                'crest_url' => $team['crest'] ?? null,
            ], fn ($v) => $v !== null) ?: ['name' => 'Onbekend'],
        )->id;
    }
}
