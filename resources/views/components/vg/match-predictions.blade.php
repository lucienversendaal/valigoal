@props([
    'match',
    'predictions' => null,
])

@php
    $predictions = ($predictions ?? collect())->sortBy(fn ($p) => $p->user?->name);
    $finished = $match->hasResult();
    $actualOutcome = $finished ? ($match->home_score <=> $match->away_score) : null;
@endphp

<flux:card class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <x-vg.team-badge :team="$match->homeTeam" reverse />
            @if ($finished)
                <span class="font-display text-xl font-bold">{{ $match->home_score }}–{{ $match->away_score }}</span>
            @else
                <flux:badge :color="$match->status->isLive() ? 'red' : 'zinc'" size="sm">{{ $match->status->label() }}</flux:badge>
            @endif
            <x-vg.team-badge :team="$match->awayTeam" />
        </div>
        <div class="flex items-center gap-2">
            @if ($match->groupLetter())
                <flux:badge color="zinc" size="sm">Groep {{ $match->groupLetter() }}</flux:badge>
            @endif
            @if ($match->kickoff_at)
                <flux:text class="text-xs">{{ $match->kickoff_at->timezone('Europe/Amsterdam')->isoFormat('ddd D MMM HH:mm') }}</flux:text>
                <span class="text-zinc-300 dark:text-zinc-600">·</span>
            @endif
            <flux:text class="text-xs">{{ $predictions->count() }} voorspellingen</flux:text>
        </div>
    </div>

    @if ($predictions->isEmpty())
        <flux:text class="text-sm">Niemand heeft deze wedstrijd voorspeld.</flux:text>
    @else
        <div class="grid gap-1.5 sm:grid-cols-2">
            @foreach ($predictions as $p)
                @php
                    $exact = $finished && $p->home_score === $match->home_score && $p->away_score === $match->away_score;
                    $outcomeOk = $finished && ! $exact && ($p->home_score <=> $p->away_score) === $actualOutcome;
                    $missed = $finished && ! $exact && ! $outcomeOk;
                @endphp
                <div @class([
                    'flex items-center justify-between gap-2 rounded-lg px-3 py-1.5 text-sm ring-1',
                    'bg-gold-400/15 ring-gold-400/40' => $exact,
                    'bg-brand-500/10 ring-brand-500/30' => $outcomeOk,
                    'bg-zinc-50 ring-transparent dark:bg-zinc-800/40 text-zinc-500' => $missed,
                    'bg-zinc-50 ring-transparent dark:bg-zinc-800/60' => ! $finished,
                ])>
                    <span class="truncate">{{ $p->user?->name }}</span>
                    <span class="flex items-center gap-2">
                        <span class="font-display font-semibold">{{ $p->home_score }}–{{ $p->away_score }}</span>
                        @if ($finished)
                            <flux:badge :color="$exact ? 'amber' : ($outcomeOk ? 'green' : 'zinc')" size="sm">+{{ $p->points ?? 0 }}</flux:badge>
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</flux:card>
