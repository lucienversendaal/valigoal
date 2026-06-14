<?php

use App\Models\GameMatch;
use App\Models\HistoricalFact;
use App\Models\Tournament;
use App\Services\Predictions\PredictionLockService;
use App\Services\Scoring\LeaderboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function tournament(): ?Tournament
    {
        return Tournament::current();
    }

    #[Computed]
    public function standings()
    {
        return app(LeaderboardService::class)->standings();
    }

    #[Computed]
    public function me(): ?array
    {
        return $this->standings->firstWhere('user.id', Auth::id());
    }

    #[Computed]
    public function topFive()
    {
        return $this->standings->take(5);
    }

    #[Computed]
    public function groupLocksAt()
    {
        return $this->tournament?->groupStageLocksAt();
    }

    #[Computed]
    public function openToPredict()
    {
        $tournament = $this->tournament;

        return GameMatch::query()
            ->when($tournament, fn ($q) => $q->where('tournament_id', $tournament->id))
            ->upcoming()
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->whereDoesntHave('predictions', fn ($q) => $q->where('user_id', Auth::id()))
            ->with(['homeTeam', 'awayTeam'])
            ->get()
            ->each(fn (GameMatch $m) => $tournament && $m->setRelation('tournament', $tournament))
            ->reject(fn (GameMatch $m) => $m->isLocked())
            ->take(4)
            ->values();
    }

    #[Computed]
    public function liveYear(): int
    {
        return (int) ($this->tournament?->season ?: ($this->tournament?->starts_at?->year ?? now()->year));
    }

    #[Computed]
    public function topScorers()
    {
        return HistoricalFact::where('type', 'wc_top_scorer')
            ->where('year', $this->liveYear)
            ->orderBy('sort_order')
            ->take(5)
            ->get();
    }

    /**
     * The match to highlight "now": a live match if there is one, otherwise the
     * most recently kicked-off match (so it is locked and predictions are shown).
     */
    #[Computed]
    public function currentMatch(): ?GameMatch
    {
        $tournament = $this->tournament;

        $base = GameMatch::query()
            ->when($tournament, fn ($q) => $q->where('tournament_id', $tournament->id))
            ->whereNotNull('kickoff_at')
            ->with(['homeTeam', 'awayTeam']);

        $match = (clone $base)
            ->whereIn('status', ['IN_PLAY', 'PAUSED'])
            ->orderByDesc('kickoff_at')
            ->first()
            ?? (clone $base)
                ->where('kickoff_at', '<', now())
                ->orderByDesc('kickoff_at')
                ->first();

        if ($match) {
            $tournament && $match->setRelation('tournament', $tournament);
        }

        return $match && $match->isLocked() ? $match : null;
    }

    /**
     * The next match to be played. Other participants' predictions only become
     * visible once it locks; until then we just show your own.
     */
    #[Computed]
    public function nextMatch(): ?GameMatch
    {
        $tournament = $this->tournament;

        $match = GameMatch::query()
            ->when($tournament, fn ($q) => $q->where('tournament_id', $tournament->id))
            ->upcoming()
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->with(['homeTeam', 'awayTeam', 'predictions' => fn ($q) => $q->where('user_id', Auth::id())])
            ->first();

        if ($match) {
            $tournament && $match->setRelation('tournament', $tournament);
        }

        return $match;
    }
}; ?>

<div class="mx-auto w-full max-w-6xl space-y-6">
        {{-- Greeting --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="font-display !text-3xl">
                    Hoi {{ Str::before(auth()->user()->name, ' ') }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ $this->tournament?->name ?? 'Geen actief toernooi' }}
                    @if ($this->tournament?->bonusesAreLocked() === false && $this->tournament?->knockoutStartsAt())
                        · bonusvragen sluiten {{ $this->tournament->knockoutStartsAt()->diffForHumans() }}
                    @endif
                </flux:text>
            </div>
            <flux:button :href="route('predictions')" wire:navigate variant="primary" icon="pencil-square">
                Voorspellen
            </flux:button>
        </div>

        {{-- Group stage countdown --}}
        @if ($this->groupLocksAt && $this->groupLocksAt->isFuture())
            <flux:card class="flex flex-col gap-3 border-brand-500/30 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading class="font-display">Groepsfase sluit binnenkort</flux:heading>
                    <flux:text class="!mt-0 text-sm">Vul je voorspellingen in vóór de aftrap van de eerste wedstrijd.</flux:text>
                </div>
                <x-vg.countdown :until="$this->groupLocksAt" label="Nog te gaan" expired="Groepsfase gesloten" />
            </flux:card>
        @endif

        {{-- Stat cards --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-vg.stat icon="trophy" label="Mijn positie" :value="$this->me ? '#'.$this->me['rank'] : '—'" accent="gold" />
            <x-vg.stat icon="bolt" label="Totaal punten" :value="$this->me['total_points'] ?? 0" accent="brand" />
            <x-vg.stat icon="check-badge" label="Exacte uitslagen" :value="$this->me['exact_count'] ?? 0" accent="brand" />
            <x-vg.stat icon="pencil-square" label="Voorspellingen" :value="$this->me['predictions_count'] ?? 0" accent="brand" />
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- To predict --}}
            <div class="lg:col-span-2 space-y-6">
                <flux:card class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg" class="font-display">Nog te voorspellen</flux:heading>
                        <flux:badge color="cyan" size="sm">{{ $this->openToPredict->count() }} open</flux:badge>
                    </div>

                    @forelse ($this->openToPredict as $match)
                        <a href="{{ route('predictions') }}" wire:navigate
                           class="flex items-center justify-between rounded-xl border border-zinc-200 p-3 transition-colors hover:border-brand-400 hover:bg-brand-50/50 dark:border-zinc-700 dark:hover:border-brand-500 dark:hover:bg-brand-500/10">
                            <div class="flex items-center gap-3">
                                <x-vg.team-badge :team="$match->homeTeam" />
                                <span class="text-sm font-semibold text-zinc-500">vs</span>
                                <x-vg.team-badge :team="$match->awayTeam" />
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-medium text-zinc-500">{{ $match->kickoff_at?->timezone('Europe/Amsterdam')->isoFormat('ddd D MMM') }}</div>
                                <div class="font-display text-sm font-semibold">{{ $match->kickoff_at?->timezone('Europe/Amsterdam')->format('H:i') }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700">
                            Je bent helemaal bij — geen open wedstrijden om te voorspellen.
                        </div>
                    @endforelse
                </flux:card>

                {{-- Now & next, with everyone's predictions --}}
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="font-display">Wedstrijden</flux:heading>
                    <flux:link :href="route('results')" wire:navigate class="text-sm">Alle uitslagen &rarr;</flux:link>
                </div>

                @if ($this->currentMatch)
                    <div class="space-y-2">
                        <flux:text class="text-xs font-semibold uppercase tracking-wide text-zinc-400">
                            {{ $this->currentMatch->status->isLive() ? 'Nu live' : 'Laatst gespeeld' }}
                        </flux:text>
                        <x-vg.match-predictions
                            :match="$this->currentMatch"
                            :predictions="app(PredictionLockService::class)->visiblePredictions($this->currentMatch)" />
                    </div>
                @endif

                @if ($this->nextMatch)
                    <div class="space-y-2">
                        <flux:text class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Eerstvolgende wedstrijd</flux:text>
                        @if ($this->nextMatch->isLocked())
                            <x-vg.match-predictions
                                :match="$this->nextMatch"
                                :predictions="app(PredictionLockService::class)->visiblePredictions($this->nextMatch)" />
                        @else
                            <x-vg.match-fixture :match="$this->nextMatch" :prediction="$this->nextMatch->predictions->first()" />
                        @endif
                    </div>
                @endif

                @unless ($this->currentMatch || $this->nextMatch)
                    <flux:card><flux:text>Nog geen wedstrijden om te tonen.</flux:text></flux:card>
                @endunless
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
            {{-- Mini leaderboard --}}
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="font-display">Top 5</flux:heading>
                    <flux:link :href="route('leaderboard')" wire:navigate class="text-sm">Volledig &rarr;</flux:link>
                </div>
                <div class="space-y-1">
                    @foreach ($this->topFive as $row)
                        <div @class([
                            'flex items-center justify-between rounded-lg px-3 py-2',
                            'bg-brand-50 dark:bg-brand-500/10' => $row['user']->id === auth()->id(),
                        ])>
                            <div class="flex items-center gap-3">
                                <span @class([
                                    'flex size-6 items-center justify-center rounded-full text-xs font-bold',
                                    'bg-gold-400 text-pitch-950' => $row['rank'] === 1,
                                    'bg-zinc-300 text-zinc-700 dark:bg-zinc-600 dark:text-white' => $row['rank'] === 2,
                                    'bg-amber-700/80 text-white' => $row['rank'] === 3,
                                    'text-zinc-500' => $row['rank'] > 3,
                                ])>{{ $row['rank'] }}</span>
                                <span class="truncate text-sm font-medium">{{ $row['name'] }}</span>
                            </div>
                            <span class="font-display font-bold text-brand-600 dark:text-brand-400">{{ $row['total_points'] }}</span>
                        </div>
                    @endforeach
                </div>

                <flux:separator />

                <flux:button :href="route('bonus')" wire:navigate variant="ghost" class="w-full" icon="star">
                    Bonusvragen
                </flux:button>
            </flux:card>

            {{-- Live top scorers --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="font-display">Topscorers</flux:heading>
                    <span class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-400">
                        <span class="relative flex size-2">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-green-500/70"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-green-500"></span>
                        </span>
                        WK {{ $this->liveYear }}
                    </span>
                </div>
                <x-vg.scorer-list :rows="$this->topScorers" :goldFirst="true"
                    empty="Nog geen goals — verschijnt zodra het WK begint." />
            </div>
            </div>
        </div>
    </div>
</div>
