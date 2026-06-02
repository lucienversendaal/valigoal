@props([
    'match',
])

@if ($match->hasOdds())
    @php($fav = $match->favouriteOutcome())
    <div class="flex items-center justify-center gap-1.5 text-xs" title="Gemiddelde bookmaker-odds (1X2)">
        <span class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400">Odds</span>
        @foreach ([['k' => '1', 'v' => $match->odds['home'], 'side' => 'HOME_TEAM'], ['k' => 'X', 'v' => $match->odds['draw'], 'side' => 'DRAW'], ['k' => '2', 'v' => $match->odds['away'], 'side' => 'AWAY_TEAM']] as $o)
            <span @class([
                'inline-flex items-center gap-1 rounded-md px-2 py-0.5 tabular-nums',
                'bg-brand-500/15 font-semibold text-brand-700 ring-1 ring-brand-500/30 dark:text-brand-300' => $fav === $o['side'],
                'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $fav !== $o['side'],
            ])>
                <span class="text-zinc-400">{{ $o['k'] }}</span>{{ number_format($o['v'], 2) }}
            </span>
        @endforeach
    </div>
@endif
