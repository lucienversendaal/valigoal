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
    public function matches()
    {
        return GameMatch::query()
            ->when($this->tournament, fn ($q) => $q->where('tournament_id', $this->tournament->id))
            ->whereNotNull('kickoff_at')
            ->with(['homeTeam', 'awayTeam'])
            ->withCount('predictions')
            ->orderBy('kickoff_at')
            ->get()
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
            <flux:text class="mt-1">Vul je uitslag in vóór de aftrap. Daarna sluit de wedstrijd automatisch.</flux:text>
        </div>

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

        {{-- Locked / live --}}
        @if (($this->matches['locked'] ?? collect())->isNotEmpty())
            <section class="space-y-3">
                <flux:heading size="lg" class="font-display">Gesloten &amp; live</flux:heading>
                <flux:text class="!mt-0 text-sm">Voorspellingen zijn nu zichtbaar voor iedereen.</flux:text>

                @foreach ($this->matches['locked'] as $match)
                    <flux:card class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <x-vg.team-badge :team="$match->homeTeam" reverse />
                                <flux:badge :color="$match->status->isLive() ? 'red' : 'zinc'" size="sm">{{ $match->status->label() }}</flux:badge>
                                <x-vg.team-badge :team="$match->awayTeam" />
                            </div>
                            <flux:text class="text-xs">{{ $match->predictions_count }} voorspellingen</flux:text>
                        </div>
                        <flux:accordion>
                            <flux:accordion.item heading="Bekijk alle voorspellingen">
                                <div class="grid gap-1 sm:grid-cols-2">
                                    @foreach (app(PredictionLockService::class)->visiblePredictions($match)->sortByDesc('user_id') as $p)
                                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-1.5 text-sm dark:bg-zinc-800/60">
                                            <span class="truncate">{{ $p->user->name }}</span>
                                            <span class="font-display font-semibold">{{ $p->home_score }}–{{ $p->away_score }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </flux:accordion.item>
                        </flux:accordion>
                    </flux:card>
                @endforeach
            </section>
        @endif

        {{-- Finished --}}
        @if (($this->matches['finished'] ?? collect())->isNotEmpty())
            <section class="space-y-3">
                <flux:heading size="lg" class="font-display">Afgelopen</flux:heading>
                @foreach ($this->matches['finished'] as $match)
                    @php($pred = $this->scores[$match->id] ?? null)
                    <flux:card class="flex items-center justify-between !py-3">
                        <div class="flex items-center gap-3">
                            <x-vg.team-badge :team="$match->homeTeam" reverse />
                            <span class="font-display text-xl font-bold">{{ $match->home_score }}–{{ $match->away_score }}</span>
                            <x-vg.team-badge :team="$match->awayTeam" />
                        </div>
                        @if ($pred)
                            <span class="text-sm text-zinc-500">jouw voorspelling: <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $pred['home'] }}–{{ $pred['away'] }}</span></span>
                        @else
                            <flux:badge color="zinc" size="sm">niet voorspeld</flux:badge>
                        @endif
                    </flux:card>
                @endforeach
            </section>
        @endif
    </div>
