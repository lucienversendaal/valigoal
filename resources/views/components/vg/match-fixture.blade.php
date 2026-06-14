@props([
    'match',
    'prediction' => null,
])

{{-- An upcoming match whose predictions are still secret: show the fixture,
     your own prediction and a CTA. Others appear once the match locks. --}}
<flux:card class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <x-vg.team-badge :team="$match->homeTeam" reverse />
            <span class="font-display text-lg text-zinc-400">–</span>
            <x-vg.team-badge :team="$match->awayTeam" />
        </div>
        <div class="flex items-center gap-2">
            @if ($match->groupLetter())
                <flux:badge color="zinc" size="sm">Groep {{ $match->groupLetter() }}</flux:badge>
            @endif
            <flux:text class="text-xs">{{ $match->kickoff_at->timezone('Europe/Amsterdam')->isoFormat('ddd D MMM HH:mm') }}</flux:text>
        </div>
    </div>

    <flux:separator />

    <div class="flex flex-wrap items-center justify-between gap-3">
        @if ($prediction)
            <flux:text class="text-sm">Jouw voorspelling: <span class="font-display font-semibold text-zinc-800 dark:text-zinc-100">{{ $prediction->home_score }}–{{ $prediction->away_score }}</span></flux:text>
            <flux:button :href="route('predictions')" wire:navigate variant="ghost" size="sm" icon="pencil-square">Aanpassen</flux:button>
        @else
            <flux:text class="text-sm text-zinc-500">Je hebt deze nog niet voorspeld.</flux:text>
            <flux:button :href="route('predictions')" wire:navigate variant="primary" size="sm" icon="pencil-square">Voorspellen</flux:button>
        @endif
    </div>

    <flux:text class="text-xs text-zinc-400">Voorspellingen van anderen verschijnen zodra de wedstrijd sluit.</flux:text>
</flux:card>
