<?php

namespace App\Support;

use App\Models\GameMatch;
use Illuminate\Support\Str;

/**
 * Official FIFA World Cup 2026 knockout bracket template.
 *
 * Each stage lists its ties in chronological (kickoff) order — the same order
 * football-data returns them — so they can be zipped onto the synced matches
 * by index. Labels describe the source of each slot (group position or the
 * winner/loser of an earlier tie) and are shown until the real team is known.
 */
class KnockoutSchema
{
    /**
     * @return array<int, array{number: int, venue: string, home: string, away: string}>
     */
    public static function forStage(string $stage): array
    {
        return self::all()[$stage] ?? [];
    }

    /**
     * Knockout stages in chronological order.
     *
     * @return array<int, string>
     */
    public static function knockoutStages(): array
    {
        return array_keys(self::all());
    }

    /**
     * Every prediction round in chronological order, group stage first.
     *
     * @return array<int, string>
     */
    public static function reminderStages(): array
    {
        return [GameMatch::GROUP_STAGE, ...self::knockoutStages()];
    }

    /**
     * The round whose results unlock the given stage for predictions. Returns
     * null for stages that have no predecessor (i.e. the group stage).
     */
    public static function predecessor(string $stage): ?string
    {
        return match ($stage) {
            'LAST_32' => GameMatch::GROUP_STAGE,
            'LAST_16' => 'LAST_32',
            'QUARTER_FINALS' => 'LAST_16',
            'SEMI_FINALS' => 'QUARTER_FINALS',
            'THIRD_PLACE', 'FINAL' => 'SEMI_FINALS',
            default => null,
        };
    }

    /**
     * Human-readable Dutch label for a stage, used in e-mails.
     */
    public static function label(string $stage): string
    {
        return match ($stage) {
            GameMatch::GROUP_STAGE => 'groepsfase',
            'LAST_32' => 'Last 32',
            'LAST_16' => 'achtste finales',
            'QUARTER_FINALS' => 'kwartfinales',
            'SEMI_FINALS' => 'halve finales',
            'THIRD_PLACE' => 'troostfinale',
            'FINAL' => 'finale',
            default => Str::headline(strtolower($stage)),
        };
    }

    /**
     * @return array<string, array<int, array{number: int, venue: string, home: string, away: string}>>
     */
    public static function all(): array
    {
        return [
            'LAST_32' => [
                ['number' => 73, 'venue' => 'Los Angeles', 'home' => 'Nr. 2 Poule A', 'away' => 'Nr. 2 Poule B'],
                ['number' => 76, 'venue' => 'Houston', 'home' => 'Winnaar Poule C', 'away' => 'Nr. 2 Poule F'],
                ['number' => 74, 'venue' => 'Boston', 'home' => 'Winnaar Poule E', 'away' => 'Nr. 3 (A/B/C/D/F)'],
                ['number' => 75, 'venue' => 'Monterrey', 'home' => 'Winnaar Poule F', 'away' => 'Nr. 2 Poule C'],
                ['number' => 78, 'venue' => 'Dallas', 'home' => 'Nr. 2 Poule E', 'away' => 'Nr. 2 Poule I'],
                ['number' => 77, 'venue' => 'New York/New Jersey', 'home' => 'Winnaar Poule I', 'away' => 'Nr. 3 (C/D/F/G/H)'],
                ['number' => 79, 'venue' => 'Mexico-Stad', 'home' => 'Winnaar Poule A', 'away' => 'Nr. 3 (C/E/F/H/I)'],
                ['number' => 80, 'venue' => 'Atlanta', 'home' => 'Winnaar Poule L', 'away' => 'Nr. 3 (E/H/I/J/K)'],
                ['number' => 82, 'venue' => 'San Francisco', 'home' => 'Winnaar Poule G', 'away' => 'Nr. 3 (A/E/H/I/J)'],
                ['number' => 81, 'venue' => 'Seattle', 'home' => 'Winnaar Poule D', 'away' => 'Nr. 3 (B/E/F/I/J)'],
                ['number' => 84, 'venue' => 'Los Angeles', 'home' => 'Winnaar Poule H', 'away' => 'Nr. 2 Poule J'],
                ['number' => 83, 'venue' => 'Toronto', 'home' => 'Nr. 2 Poule K', 'away' => 'Nr. 2 Poule L'],
                ['number' => 85, 'venue' => 'Vancouver', 'home' => 'Winnaar Poule B', 'away' => 'Nr. 3 (E/F/G/I/J)'],
                ['number' => 88, 'venue' => 'Dallas', 'home' => 'Nr. 2 Poule D', 'away' => 'Nr. 2 Poule G'],
                ['number' => 86, 'venue' => 'Miami', 'home' => 'Winnaar Poule J', 'away' => 'Nr. 2 Poule H'],
                ['number' => 87, 'venue' => 'Kansas City', 'home' => 'Winnaar Poule K', 'away' => 'Nr. 3 (D/E/I/J/L)'],
            ],
            'LAST_16' => [
                ['number' => 90, 'venue' => 'Houston', 'home' => 'Winnaar duel 73', 'away' => 'Winnaar duel 75'],
                ['number' => 89, 'venue' => 'Philadelphia', 'home' => 'Winnaar duel 74', 'away' => 'Winnaar duel 77'],
                ['number' => 91, 'venue' => 'New York/New Jersey', 'home' => 'Winnaar duel 76', 'away' => 'Winnaar duel 78'],
                ['number' => 92, 'venue' => 'Mexico-Stad', 'home' => 'Winnaar duel 79', 'away' => 'Winnaar duel 80'],
                ['number' => 93, 'venue' => 'Dallas', 'home' => 'Winnaar duel 83', 'away' => 'Winnaar duel 84'],
                ['number' => 94, 'venue' => 'Seattle', 'home' => 'Winnaar duel 81', 'away' => 'Winnaar duel 82'],
                ['number' => 95, 'venue' => 'Atlanta', 'home' => 'Winnaar duel 86', 'away' => 'Winnaar duel 88'],
                ['number' => 96, 'venue' => 'Vancouver', 'home' => 'Winnaar duel 85', 'away' => 'Winnaar duel 87'],
            ],
            'QUARTER_FINALS' => [
                ['number' => 97, 'venue' => 'Boston', 'home' => 'Winnaar duel 89', 'away' => 'Winnaar duel 90'],
                ['number' => 98, 'venue' => 'Los Angeles', 'home' => 'Winnaar duel 93', 'away' => 'Winnaar duel 94'],
                ['number' => 99, 'venue' => 'Miami', 'home' => 'Winnaar duel 91', 'away' => 'Winnaar duel 92'],
                ['number' => 100, 'venue' => 'Kansas City', 'home' => 'Winnaar duel 95', 'away' => 'Winnaar duel 96'],
            ],
            'SEMI_FINALS' => [
                ['number' => 101, 'venue' => 'Dallas', 'home' => 'Winnaar duel 97', 'away' => 'Winnaar duel 98'],
                ['number' => 102, 'venue' => 'Atlanta', 'home' => 'Winnaar duel 99', 'away' => 'Winnaar duel 100'],
            ],
            'THIRD_PLACE' => [
                ['number' => 103, 'venue' => 'Miami', 'home' => 'Verliezer duel 101', 'away' => 'Verliezer duel 102'],
            ],
            'FINAL' => [
                ['number' => 104, 'venue' => 'New York/New Jersey', 'home' => 'Winnaar duel 101', 'away' => 'Winnaar duel 102'],
            ],
        ];
    }
}
