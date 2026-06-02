<?php

use App\Exceptions\PredictionLockedException;
use App\Models\GameMatch;
use App\Models\Standing;
use App\Models\Tournament;
use App\Services\Predictions\PredictionLockService;
use App\Services\Scoring\PredictedStandingService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $group;

    /** @var array<int, array{home: ?int, away: ?int}> */
    public array $scores = [];

    public function mount(string $group): void
    {
        $this->group = strtoupper($group);

        foreach (Auth::user()->predictions as $prediction) {
            $this->scores[$prediction->match_id] = [
                'home' => $prediction->home_score,
                'away' => $prediction->away_score,
            ];
        }
    }

    #[Computed]
    public function tournament(): ?Tournament
    {
        return Tournament::current();
    }

    #[Computed]
    public function matches()
    {
        if (! $this->tournament) {
            return collect();
        }

        return GameMatch::query()
            ->where('tournament_id', $this->tournament->id)
            ->whereNotNull('group')
            ->with(['homeTeam', 'awayTeam', 'predictions' => fn ($q) => $q->where('user_id', Auth::id())])
            ->orderBy('kickoff_at')
            ->get()
            ->filter(fn (GameMatch $m) => $m->groupLetter() === $this->group)
            ->values();
    }

    #[Computed]
    public function standings()
    {
        if (! $this->tournament) {
            return collect();
        }

        return Standing::query()
            ->where('tournament_id', $this->tournament->id)
            ->with('team')
            ->orderBy('position')
            ->get()
            ->filter(fn (Standing $s) => $s->groupLetter() === $this->group)
            ->values();
    }

    #[Computed]
    public function predictedStandings()
    {
        return app(PredictedStandingService::class)->build($this->matches);
    }

    public function save(int $matchId): void
    {
        $match = GameMatch::findOrFail($matchId);

        $this->validate([
            "scores.{$matchId}.home" => ['required', 'integer', 'min:0', 'max:99'],
            "scores.{$matchId}.away" => ['required', 'integer', 'min:0', 'max:99'],
        ], [], [
            "scores.{$matchId}.home" => 'thuisscore',
            "scores.{$matchId}.away" => 'uitscore',
        ]);

        try {
            app(PredictionLockService::class)->save(
                Auth::user(),
                $match,
                (int) $this->scores[$matchId]['home'],
                (int) $this->scores[$matchId]['away'],
                request()->ip(),
            );

            unset($this->matches, $this->predictedStandings);

            Flux::toast(variant: 'success', text: 'Voorspelling opgeslagen!');
        } catch (PredictionLockedException $e) {
            unset($this->matches, $this->predictedStandings);
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }
}; ?>

<div class="mx-auto w-full max-w-6xl space-y-6">
    <div class="flex items-center gap-3">
        <flux:button :href="route('standings')" wire:navigate variant="ghost" size="sm" icon="arrow-left" class="shrink-0">Poules</flux:button>
        <div>
            <flux:heading size="xl" class="font-display !text-3xl">Groep {{ $this->group }}</flux:heading>
            <flux:text class="!mt-0">{{ $this->tournament?->name }}</flux:text>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Left: matches with inline prediction inputs --}}
        <div class="space-y-3 lg:col-span-3">
            <flux:heading size="lg" class="font-display">Wedstrijden</flux:heading>

            @forelse ($this->matches as $match)
                @php($pred = $match->predictions->first())
                <flux:card class="!p-0 overflow-hidden">
                    @if ($match->isOpen())
                        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
                            <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 sm:justify-self-start">
                                {{ $match->kickoff_at?->timezone('Europe/Amsterdam')->isoFormat('ddd D MMM HH:mm') }}
                            </div>
                            <div class="flex flex-col items-center gap-2">
                                <div class="flex items-center justify-center gap-3">
                                    <div class="flex w-24 justify-end"><x-vg.team-badge :team="$match->homeTeam" reverse /></div>
                                    <div class="flex items-center gap-2">
                                        <flux:input type="number" min="0" max="99" wire:model="scores.{{ $match->id }}.home" class="w-16 text-center" />
                                        <span class="font-display text-lg text-zinc-400">–</span>
                                        <flux:input type="number" min="0" max="99" wire:model="scores.{{ $match->id }}.away" class="w-16 text-center" />
                                    </div>
                                    <div class="flex w-24 justify-start"><x-vg.team-badge :team="$match->awayTeam" /></div>
                                </div>
                                <x-vg.odds :match="$match" />
                                @error("scores.{$match->id}.home") <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                                @error("scores.{$match->id}.away") <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:justify-self-end">
                                <flux:button wire:click="save({{ $match->id }})" wire:loading.attr="disabled" variant="primary" size="sm" icon="check">
                                    Opslaan
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <div class="space-y-2 p-4">
                            <div class="flex items-center justify-between text-xs font-medium uppercase tracking-wide text-zinc-500">
                                <span>{{ $match->kickoff_at?->timezone('Europe/Amsterdam')->isoFormat('ddd D MMM HH:mm') }}</span>
                                <flux:badge :color="$match->status->isFinished() ? 'green' : ($match->status->isLive() ? 'red' : 'zinc')" size="sm">
                                    {{ $match->status->label() }}
                                </flux:badge>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex flex-1 justify-end"><x-vg.team-badge :team="$match->homeTeam" reverse /></div>
                                <div class="shrink-0 text-center">
                                    @if ($match->status->isFinished())
                                        <span class="font-display text-xl font-bold">{{ $match->home_score }}–{{ $match->away_score }}</span>
                                    @else
                                        <span class="font-display text-lg text-zinc-400">vs</span>
                                    @endif
                                </div>
                                <div class="flex flex-1 justify-start"><x-vg.team-badge :team="$match->awayTeam" /></div>
                            </div>
                            @if ($pred)
                                <div class="text-center text-xs text-zinc-500">
                                    jouw voorspelling: <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $pred->home_score }}–{{ $pred->away_score }}</span>
                                    @if ($match->status->isFinished() && $pred->points !== null)
                                        <flux:badge :color="$pred->points >= 5 ? 'green' : ($pred->points > 0 ? 'amber' : 'zinc')" size="sm" class="ml-1">+{{ $pred->points }}</flux:badge>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </flux:card>
            @empty
                <flux:card><flux:text>Geen wedstrijden gevonden voor deze groep.</flux:text></flux:card>
            @endforelse
        </div>

        {{-- Right: actual + predicted standings --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="space-y-3">
                <flux:heading size="lg" class="font-display">Huidige stand</flux:heading>
                <x-vg.group-table :rows="$this->standings" :predicted="false" empty="Nog geen stand beschikbaar." />
            </div>

            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg" class="font-display">Jouw voorspelde stand</flux:heading>
                    <flux:icon icon="sparkles" variant="solid" class="size-4 text-gold-500" />
                </div>
                <flux:text class="!mt-0 text-sm">Op basis van jouw ingevulde uitslagen (3 ptn winst, 1 gelijk).</flux:text>
                <x-vg.group-table :rows="$this->predictedStandings" :predicted="true" empty="Vul voorspellingen in om je eigen stand te zien." />
            </div>
        </div>
    </div>
</div>
