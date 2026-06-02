@props([
    'match',
    'schema' => null,
    'highlight' => false,
])

@php
    $finished = $match->status->isFinished();
    $winner = $match->winner ?? $match->outcome();
    $pred = $match->predictions->first();
@endphp

<div @class([
    'overflow-hidden rounded-xl border',
    'border-gold-400/50' => $highlight,
    'border-zinc-200 dark:border-zinc-700' => ! $highlight,
])>
    <div class="flex items-center justify-between gap-2 bg-zinc-50 px-3 py-1.5 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-800/60">
        <span>
            @if ($schema)<span class="text-zinc-400">#{{ $schema['number'] }}</span> · @endif
            {{ $match->kickoff_at?->timezone('Europe/Amsterdam')->isoFormat('D MMM HH:mm') ?? 'Datum tbd' }}
        </span>
        <flux:badge :color="$finished ? 'green' : ($match->status->isLive() ? 'red' : 'zinc')" size="sm">{{ $match->status->label() }}</flux:badge>
    </div>

    @foreach ([['team' => $match->homeTeam, 'score' => $match->home_score, 'side' => 'HOME_TEAM', 'label' => $schema['home'] ?? 'Nader te bepalen'], ['team' => $match->awayTeam, 'score' => $match->away_score, 'side' => 'AWAY_TEAM', 'label' => $schema['away'] ?? 'Nader te bepalen']] as $i => $row)
        <div @class([
            'flex items-center gap-2 px-3 py-2 text-sm',
            'border-t border-zinc-100 dark:border-zinc-800' => $i === 1,
            'bg-brand-500/10 font-semibold' => $finished && $winner === $row['side'],
        ])>
            @if ($row['team'])
                <x-vg.team-badge :team="$row['team']" />
            @else
                <span class="truncate text-zinc-500">{{ $row['label'] }}</span>
            @endif
            @if ($finished && $row['team'])
                <span @class(['ml-auto font-display text-base font-bold', 'text-brand-600 dark:text-brand-400' => $winner === $row['side']])>{{ $row['score'] }}</span>
            @endif
        </div>
    @endforeach

    @if ($schema || $pred)
        <div class="flex items-center justify-between gap-2 border-t border-zinc-100 px-3 py-1.5 text-[11px] text-zinc-500 dark:border-zinc-800">
            @if ($schema)<span class="truncate">{{ $schema['venue'] }}</span>@else<span></span>@endif
            @if ($pred)
                <span class="shrink-0">jouw: <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $pred->home_score }}–{{ $pred->away_score }}</span>
                    @if ($finished && $pred->points !== null)<flux:badge :color="$pred->points >= 5 ? 'green' : ($pred->points > 0 ? 'amber' : 'zinc')" size="sm" class="ml-1">+{{ $pred->points }}</flux:badge>@endif
                </span>
            @endif
        </div>
    @endif
</div>
