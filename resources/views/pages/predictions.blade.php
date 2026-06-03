<?php

use App\Exceptions\PredictionLockedException;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Services\Predictions\PredictionLockService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Voorspellen')] class extends Component {
    /** @var array<int, array{home: ?int, away: ?int}> */
    public array $scores = [];

    public function mount(): void
    {
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
    public function groupLocksAt()
    {
        return $this->tournament?->groupStageLocksAt();
    }

    #[Computed]
    public function matches()
    {
        $tournament = $this->tournament;

        return GameMatch::query()
            ->when($tournament, fn ($q) => $q->where('tournament_id', $tournament->id))
            ->whereNotNull('kickoff_at')
            ->with(['homeTeam', 'awayTeam'])
            ->withCount('predictions')
            ->orderBy('kickoff_at')
            ->get()
            ->each(fn (GameMatch $m) => $tournament && $m->setRelation('tournament', $tournament))
            ->groupBy(function (GameMatch $m) {
                if ($m->status->isFinished()) {
                    return 'finished';
                }
                if ($m->isLocked()) {
                    return 'locked';
                }

                return $m->homeTeam && $m->awayTeam ? 'open' : 'tbd';
            });
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

            Flux::toast(variant: 'success', text: 'Voorspelling opgeslagen!');
        } catch (PredictionLockedException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
            unset($this->matches);
        }
    }
}; ?>

<div class="mx-auto w-full max-w-4xl space-y-8">
        <div>
            <flux:heading size="xl" class="font-display !text-3xl">Voorspellen</flux:heading>
            <flux:text class="mt-1">De groepsfase sluit in één keer bij de aftrap van de eerste wedstrijd. Knockoutwedstrijden sluiten elk op hun eigen aftrap.</flux:text>
        </div>

        {{-- Group stage deadline --}}
        @if ($this->groupLocksAt)
            <flux:card @class([
                'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between',
                '!bg-zinc-50 dark:!bg-zinc-800/40' => $this->groupLocksAt->isPast(),
            ])>
                <div>
                    <flux:heading class="font-display">Sluiting groepsfase</flux:heading>
                    <flux:text class="!mt-0 text-sm">
                        {{ $this->groupLocksAt->timezone('Europe/Amsterdam')->isoFormat('dddd D MMMM, HH:mm') }} — aftrap eerste wedstrijd.
                    </flux:text>
                </div>
                <x-vg.countdown :until="$this->groupLocksAt" label="Nog te gaan" expired="Groepsfase gesloten" />
            </flux:card>
        @endif

        {{-- Open --}}
        <section class="space-y-3">
            <div class="flex items-center gap-2">
                <flux:heading size="lg" class="font-display">Open wedstrijden</flux:heading>
                <flux:badge color="cyan" size="sm">{{ ($this->matches['open'] ?? collect())->count() }}</flux:badge>
            </div>

            @forelse ($this->matches['open'] ?? [] as $match)
                <flux:card class="!p-0 overflow-hidden">
                    <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
                        <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 sm:justify-self-start">
                            @if ($match->groupLetter())
                                <a href="{{ route('group', ['group' => $match->groupLetter()]) }}" wire:navigate
                                   class="cursor-pointer rounded text-brand-600 transition-colors hover:text-brand-500 hover:underline dark:text-brand-400">
                                    Groep {{ $match->groupLetter() }}
                                </a>
                                ·
                            @endif
                            {{ $match->kickoff_at->timezone('Europe/Amsterdam')->isoFormat('ddd D MMM HH:mm') }}
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <div class="flex items-center justify-center gap-3">
                                <div class="flex w-32 justify-end"><x-vg.team-badge :team="$match->homeTeam" reverse /></div>
                                <div class="flex items-center gap-2">
                                    <flux:input type="number" min="0" max="99" wire:model="scores.{{ $match->id }}.home" class="w-16 text-center" />
                                    <span class="font-display text-lg text-zinc-400">–</span>
                                    <flux:input type="number" min="0" max="99" wire:model="scores.{{ $match->id }}.away" class="w-16 text-center" />
                                </div>
                                <div class="flex w-32 justify-start"><x-vg.team-badge :team="$match->awayTeam" /></div>
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
                </flux:card>
            @empty
                <flux:card><flux:text>Er zijn nu geen open wedstrijden.</flux:text></flux:card>
            @endforelse
        </section>

        {{-- Not yet drawn (knockout) --}}
        @if (($this->matches['tbd'] ?? collect())->isNotEmpty())
            <section class="space-y-3">
                <flux:heading size="lg" class="font-display">Nog te loten</flux:heading>
                <flux:text class="!mt-0 text-sm">Zodra de deelnemers bekend zijn, kun je deze wedstrijden voorspellen.</flux:text>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($this->matches['tbd'] as $match)
                        <flux:card class="flex items-center justify-between !py-3 opacity-80">
                            <span class="text-sm font-medium text-zinc-500">{{ \Illuminate\Support\Str::headline(strtolower($match->stage ?? 'Knock-out')) }}</span>
                            <span class="text-xs text-zinc-500">{{ $match->kickoff_at->timezone('Europe/Amsterdam')->isoFormat('D MMM HH:mm') }}</span>
                        </flux:card>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Locked / live — everyone's predictions, per match --}}
        @if (($this->matches['locked'] ?? collect())->isNotEmpty())
            <section class="space-y-3">
                <flux:heading size="lg" class="font-display">Gesloten &amp; live</flux:heading>
                <flux:text class="!mt-0 text-sm">Voorspellingen zijn nu zichtbaar voor iedereen.</flux:text>

                @foreach ($this->matches['locked'] as $match)
                    <x-vg.match-predictions
                        :match="$match"
                        :predictions="app(PredictionLockService::class)->visiblePredictions($match)" />
                @endforeach
            </section>
        @endif

        {{-- Finished — full overview with everyone's predictions and points --}}
        @if (($this->matches['finished'] ?? collect())->isNotEmpty())
            <section class="space-y-3">
                <flux:heading size="lg" class="font-display">Afgelopen</flux:heading>
                <flux:text class="!mt-0 text-sm">Goud = exact, groen = juiste uitkomst, grijs = mis.</flux:text>
                @foreach ($this->matches['finished'] as $match)
                    <x-vg.match-predictions
                        :match="$match"
                        :predictions="app(PredictionLockService::class)->visiblePredictions($match)" />
                @endforeach
            </section>
        @endif
    </div>
