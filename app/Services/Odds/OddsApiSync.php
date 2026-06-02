<?php

namespace App\Services\Odds;

use App\Models\GameMatch;
use App\Models\Tournament;
use Illuminate\Support\Str;

class OddsApiSync
{
    public function __construct(protected OddsApiClient $client) {}

    /**
     * Fetch 1X2 odds and attach the averaged decimal odds to matching fixtures.
     *
     * @return array{events: int, matched: int}
     */
    public function sync(Tournament $tournament): array
    {
        $events = $this->client->events();

        $matches = GameMatch::query()
            ->where('tournament_id', $tournament->id)
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        $byPair = $matches->keyBy(fn (GameMatch $m) => $this->pairKey($m->homeTeam->name, $m->awayTeam->name));

        $matched = 0;

        foreach ($events as $event) {
            $home = $event['home_team'] ?? null;
            $away = $event['away_team'] ?? null;

            if (! $home || ! $away) {
                continue;
            }

            $match = $byPair->get($this->pairKey($home, $away));

            if (! $match) {
                continue;
            }

            $prices = $this->averagePrices($event['bookmakers'] ?? []);

            $homeOdd = $prices[$this->normalize($match->homeTeam->name)] ?? null;
            $awayOdd = $prices[$this->normalize($match->awayTeam->name)] ?? null;
            $drawOdd = $prices['draw'] ?? null;

            if ($homeOdd === null || $awayOdd === null || $drawOdd === null) {
                continue;
            }

            $match->forceFill([
                'odds' => [
                    'home' => round($homeOdd, 2),
                    'draw' => round($drawOdd, 2),
                    'away' => round($awayOdd, 2),
                    'bookmakers' => count($event['bookmakers'] ?? []),
                ],
                'odds_updated_at' => now(),
            ])->save();

            $matched++;
        }

        return ['events' => count($events), 'matched' => $matched];
    }

    /**
     * Average each outcome's decimal price across all bookmakers.
     *
     * @param  array<int, array<string, mixed>>  $bookmakers
     * @return array<string, float> normalized outcome name => average price
     */
    protected function averagePrices(array $bookmakers): array
    {
        $totals = [];
        $counts = [];

        foreach ($bookmakers as $bookmaker) {
            foreach ($bookmaker['markets'] ?? [] as $market) {
                if (($market['key'] ?? null) !== 'h2h') {
                    continue;
                }

                foreach ($market['outcomes'] ?? [] as $outcome) {
                    $name = $this->normalize($outcome['name'] ?? '');
                    $price = (float) ($outcome['price'] ?? 0);

                    if ($name === '' || $price <= 0) {
                        continue;
                    }

                    $totals[$name] = ($totals[$name] ?? 0) + $price;
                    $counts[$name] = ($counts[$name] ?? 0) + 1;
                }
            }
        }

        $averages = [];
        foreach ($totals as $name => $total) {
            $averages[$name] = $total / $counts[$name];
        }

        return $averages;
    }

    protected function pairKey(string $a, string $b): string
    {
        $teams = [$this->normalize($a), $this->normalize($b)];
        sort($teams);

        return implode('|', $teams);
    }

    /** Reconcile naming differences between football-data and The Odds API. */
    protected const ALIASES = [
        'czechrepublic' => 'czechia',
        'congodr' => 'drcongo',
        'usa' => 'unitedstates',
        'capeverdeislands' => 'capeverde',
        'unitedstatesofamerica' => 'unitedstates',
        'iranislamicrepublicof' => 'iran',
    ];

    protected function normalize(string $name): string
    {
        $key = (string) Str::of($name)->lower()->ascii()->replaceMatches('/[^a-z0-9]/', '');

        return self::ALIASES[$key] ?? $key;
    }
}
