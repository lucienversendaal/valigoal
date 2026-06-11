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

        $fullTime = $match['score']['fullTime'] ?? [];
        $incomingHasScore = isset($fullTime['home'], $fullTime['away']);

        // football-data occasionally returns a match without its fullTime score
        // (e.g. it flags the opening game as FINISHED before publishing the result).
        // Keep any score we already stored rather than wiping it back to null.
        $homeScore = $incomingHasScore ? $fullTime['home'] : $existing?->home_score;
        $awayScore = $incomingHasScore ? $fullTime['away'] : $existing?->away_score;
        $winner = $incomingHasScore ? ($match['score']['winner'] ?? null) : $existing?->winner;

        $status = $match['status'] ?? 'SCHEDULED';

        // Don't record a match as FINISHED until we actually have a result, so it
        // isn't shown as played (and skipped by scoring) without a score.
        if ($status === 'FINISHED' && ($homeScore === null || $awayScore === null)) {
            $status = 'TIMED';
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
                'finished_at' => $status === 'FINISHED' ? ($existing?->finished_at ?? now()) : null,
                'last_synced_at' => now(),
            ],
        );
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
