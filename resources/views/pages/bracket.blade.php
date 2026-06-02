<?php

use App\Models\GameMatch;
use App\Models\Tournament;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Knockoutschema')] class extends Component
{
    /** Knockout stages in bracket order. */
    public const STAGES = [
        'LAST_32' => 'Laatste 32',
        'LAST_16' => 'Achtste finale',
        'QUARTER_FINALS' => 'Kwartfinale',
        'SEMI_FINALS' => 'Halve finale',
        'FINAL' => 'Finale',
    ];

    #[Computed]
    public function tournament(): ?Tournament
    {
        return Tournament::current();
    }

    #[Computed]
    public function matchesByStage()
    {
        if (! $this->tournament) {
            return collect();
        }

        return GameMatch::query()
            ->where('tournament_id', $this->tournament->id)
            ->whereIn('stage', array_merge(array_keys(self::STAGES), ['THIRD_PLACE']))
            ->with(['homeTeam', 'awayTeam', 'predictions' => fn ($q) => $q->where('user_id', Auth::id())])
            ->orderBy('kickoff_at')
            ->orderBy('id')
            ->get()
            ->groupBy('stage');
    }

    #[Computed]
    public function thirdPlace(): ?GameMatch
    {
        return $this->matchesByStage->get('THIRD_PLACE')?->first();
    }
}; ?>

<div class="mx-auto w-full max-w-7xl space-y-6">
    <div>
        <flux:heading size="xl" class="font-display !text-3xl">Knockoutschema</flux:heading>
        <flux:text class="mt-1">Het echte schema van {{ $this->tournament?->name ?? 'het toernooi' }}. Ploegen en uitslagen vullen zich automatisch zodra ze bekend zijn.</flux:text>
    </div>

    @if ($this->matchesByStage->isEmpty())
        <flux:card><flux:text>Er is nog geen knockoutschema beschikbaar.</flux:text></flux:card>
    @else
        <div class="overflow-x-auto pb-4">
            <div class="flex min-w-max gap-4">
                @foreach (self::STAGES as $stageKey => $stageLabel)
                    @php($ties = $this->matchesByStage->get($stageKey, collect()))
                    <div @class([
                        'flex shrink-0 flex-col',
                        'w-64' => $stageKey !== 'FINAL',
                        'w-72' => $stageKey === 'FINAL',
                    ])>
                        <div class="mb-3 text-center text-xs font-bold uppercase tracking-wider text-zinc-500">
                            {{ $stageLabel }}
                            @if ($ties->isNotEmpty())
                                <span class="text-zinc-400">({{ $ties->count() }})</span>
                            @endif
                        </div>
                        @php($schema = \App\Support\KnockoutSchema::forStage($stageKey))
                        <div class="flex flex-1 flex-col justify-around gap-2">
                            @forelse ($ties as $match)
                                <x-vg.bracket-tie :match="$match" :schema="$schema[$loop->index] ?? null" :highlight="$stageKey === 'FINAL'" />
                            @empty
                                <flux:card class="!py-3 text-center text-sm text-zinc-400">Nog niet bekend</flux:card>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($this->thirdPlace)
            <div class="max-w-sm space-y-2">
                <div class="text-xs font-bold uppercase tracking-wider text-zinc-500">Troostfinale (3e/4e plaats)</div>
                <x-vg.bracket-tie :match="$this->thirdPlace" :schema="\App\Support\KnockoutSchema::forStage('THIRD_PLACE')[0] ?? null" />
            </div>
        @endif

        <flux:callout variant="secondary" icon="information-circle" class="text-sm">
            De deelnemers worden bepaald door de echte uitslagen. Voorspellen voor deze wedstrijden kan op de
            <flux:link :href="route('predictions')" wire:navigate>voorspelpagina</flux:link> zodra de ploegen bekend zijn.
        </flux:callout>
    @endif
</div>
