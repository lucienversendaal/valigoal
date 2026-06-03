<?php

namespace Database\Seeders;

use App\Models\HistoricalFact;
use Illuminate\Database\Seeder;

class HistoricalFactSeeder extends Seeder
{
    public function run(): void
    {
        $winners = [
            ['title' => 'Argentinië', 'subtitle' => 'Finale tegen Frankrijk (4-2 n.s.)', 'year' => 2022],
            ['title' => 'Frankrijk', 'subtitle' => 'Finale tegen Kroatië (4-2)', 'year' => 2018],
            ['title' => 'Duitsland', 'subtitle' => 'Finale tegen Argentinië (1-0)', 'year' => 2014],
            ['title' => 'Spanje', 'subtitle' => 'Finale tegen Nederland (1-0)', 'year' => 2010],
            ['title' => 'Italië', 'subtitle' => 'Finale tegen Frankrijk (5-3 n.s.)', 'year' => 2006],
        ];

        foreach ($winners as $i => $w) {
            HistoricalFact::updateOrCreate(
                ['type' => 'previous_winner', 'year' => $w['year']],
                [...$w, 'sort_order' => $i],
            );
        }

        $scorers = [
            ['title' => 'Miroslav Klose', 'subtitle' => 'Duitsland', 'meta' => ['goals' => 16], 'sort_order' => 0],
            ['title' => 'Ronaldo', 'subtitle' => 'Brazilië', 'meta' => ['goals' => 15], 'sort_order' => 1],
            ['title' => 'Gerd Müller', 'subtitle' => 'Duitsland', 'meta' => ['goals' => 14], 'sort_order' => 2],
            ['title' => 'Just Fontaine', 'subtitle' => 'Frankrijk', 'meta' => ['goals' => 13], 'sort_order' => 3],
            ['title' => 'Lionel Messi', 'subtitle' => 'Argentinië', 'meta' => ['goals' => 13], 'sort_order' => 4],
        ];

        foreach ($scorers as $s) {
            HistoricalFact::updateOrCreate(
                ['type' => 'all_time_top_scorer', 'title' => $s['title']],
                $s,
            );
        }

        // Golden Boot rankings of the last two World Cups (historical seasons
        // are restricted on the football-data free plan, so seeded statically).
        $recentScorers = [
            2022 => [
                ['title' => 'Kylian Mbappé', 'subtitle' => 'Frankrijk', 'goals' => 8],
                ['title' => 'Lionel Messi', 'subtitle' => 'Argentinië', 'goals' => 7],
                ['title' => 'Julián Álvarez', 'subtitle' => 'Argentinië', 'goals' => 4],
                ['title' => 'Olivier Giroud', 'subtitle' => 'Frankrijk', 'goals' => 4],
            ],
            2018 => [
                ['title' => 'Harry Kane', 'subtitle' => 'Engeland', 'goals' => 6],
                ['title' => 'Antoine Griezmann', 'subtitle' => 'Frankrijk', 'goals' => 4],
                ['title' => 'Romelu Lukaku', 'subtitle' => 'België', 'goals' => 4],
                ['title' => 'Denis Cheryshev', 'subtitle' => 'Rusland', 'goals' => 4],
            ],
        ];

        foreach ($recentScorers as $year => $players) {
            foreach ($players as $i => $player) {
                HistoricalFact::updateOrCreate(
                    ['type' => 'wc_top_scorer', 'year' => $year, 'title' => $player['title']],
                    [
                        'subtitle' => $player['subtitle'],
                        'meta' => ['goals' => $player['goals']],
                        'sort_order' => $i,
                    ],
                );
            }
        }

        $memorable = [
            [
                'title' => 'Brazilië 1 - 7 Duitsland',
                'subtitle' => 'Halve finale, 2014',
                'year' => 2014,
                'body' => 'Het gastland werd in eigen huis weggespeeld in een van de meest schokkende WK-wedstrijden ooit.',
                'sort_order' => 0,
            ],
            [
                'title' => 'Nederland 2 - 1 Argentinië',
                'subtitle' => 'Kwartfinale, 1998',
                'year' => 1998,
                'body' => 'Dennis Bergkamp met een van de mooiste WK-goals aller tijden in de laatste minuut.',
                'sort_order' => 1,
            ],
            [
                'title' => 'Frankrijk 4 - 3 Argentinië',
                'subtitle' => 'Achtste finale, 2018',
                'year' => 2018,
                'body' => 'Mbappé barstte los op het wereldtoneel in een waanzinnige zevenklapper.',
                'sort_order' => 2,
            ],
        ];

        foreach ($memorable as $m) {
            HistoricalFact::updateOrCreate(
                ['type' => 'memorable_match', 'title' => $m['title']],
                $m,
            );
        }
    }
}
