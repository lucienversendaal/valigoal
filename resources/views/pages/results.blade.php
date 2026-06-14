<?php

use App\Models\GameMatch;
use App\Models\Tournament;
use App\Services\Predictions\PredictionLockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Uitslagen')] class extends Component {
    /** Selected matchday in Y-m-d (Europe/Amsterdam). */
    public ?string $selectedDate = null;

    public function mount(): void
    {
        $days = $this->days;
        $today = now()->timezone('Europe/Amsterdam')->toDateString();

        // Prefer today when it has matches, otherwise the nearest matchday.
        $this->selectedDate = $days->contains($today)
            ? $today
            : $days->sortBy(fn (string $d) => abs(Carbon::parse($d)->diffInDays($today)))->first();
    }

    #[Computed]
    public function tournament(): ?Tournament
    {
        return Tournament::current();
    }

    /**
     * Every scheduled match for the tournament with the current user's own
     * prediction eager loaded. Locked matches reveal everyone's predictions.
     *
     * @return Collection<int, GameMatch>
     */
    #[Computed]
    public function allMatches(): Collection
    {
        $tournament = $this->tournament;

        return GameMatch::query()
            ->when($tournament, fn ($q) => $q->where('tournament_id', $tournament->id))
            ->whereNotNull('kickoff_at')
            ->with(['homeTeam', 'awayTeam', 'predictions' => fn ($q) => $q->where('user_id', Auth::id())])
            ->orderBy('kickoff_at')
            ->get()
            ->each(fn (GameMatch $m) => $tournament && $m->setRelation('tournament', $tournament));
    }

    /**
     * The matchdays (Y-m-d strings) that have at least one match, chronological.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function days(): Collection
    {
        return $this->allMatches
            ->map(fn (GameMatch $m) => $this->dateKey($m))
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Matches on the selected day.
     *
     * @return Collection<int, GameMatch>
     */
    #[Computed]
    public function dayMatches(): Collection
    {
        if ($this->selectedDate === null) {
            return collect();
        }

        return $this->allMatches
            ->filter(fn (GameMatch $m) => $this->dateKey($m) === $this->selectedDate)
            ->values();
    }

    public function selectDay(string $date): void
    {
        if ($this->days->contains($date)) {
            $this->selectedDate = $date;
        }
    }

    public function step(int $direction): void
    {
        $days = $this->days;
        $index = $days->search($this->selectedDate);

        if ($index === false) {
            return;
        }

        $target = $days->get($index + $direction);

        if ($target !== null) {
            $this->selectedDate = $target;
        }
    }

    private function dateKey(GameMatch $match): string
    {
        return $match->kickoff_at->timezone('Europe/Amsterdam')->toDateString();
    }
}; ?>

<div class="mx-auto w-full max-w-4xl space-y-8">
    <div>
        <flux:heading size="xl" class="font-display !text-3xl">Uitslagen</flux:heading>
        <flux:text class="mt-1">Bekijk per speeldag alle wedstrijden met ieders voorspellingen. Goud = exact, groen = juiste uitkomst, grijs = mis.</flux:text>
    </div>

    @if ($this->days->isEmpty())
        <flux:card><flux:text>Er zijn nog geen wedstrijden ingepland.</flux:text></flux:card>
    @else
        @php($days = $this->days)
        @php($index = $days->search($this->selectedDate))
        @php($today = now()->timezone('Europe/Amsterdam')->toDateString())

        {{-- Day picker --}}
        <div class="flex justify-center">
            <div class="flex items-center gap-1 rounded-xl border border-zinc-200 bg-white p-1 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:button icon="chevron-left" size="sm" variant="ghost"
                    wire:click="step(-1)" :disabled="$index === false || $index <= 0" />

                <flux:dropdown align="center">
                    <flux:button variant="ghost" size="sm" icon="calendar-days" class="min-w-36 justify-center font-semibold">
                        {{ \Illuminate\Support\Carbon::parse($this->selectedDate, 'Europe/Amsterdam')->isoFormat('DD/MM') }}
                        {{ strtoupper(\Illuminate\Support\Carbon::parse($this->selectedDate, 'Europe/Amsterdam')->isoFormat('dd')) }}
                    </flux:button>

                    <flux:menu class="max-h-96 overflow-y-auto text-center">
                        @foreach ($days as $day)
                            @php($date = \Illuminate\Support\Carbon::parse($day, 'Europe/Amsterdam'))
                            <flux:menu.item wire:click="selectDay('{{ $day }}')" @class([
                                'justify-center font-semibold',
                                'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' => $day === $today,
                                '!text-brand-600 dark:!text-brand-400' => $day === $this->selectedDate && $day !== $today,
                            ])>
                                @if ($day === $today)
                                    VANDAAG
                                @else
                                    {{ $date->isoFormat('DD/MM') }} {{ strtoupper($date->isoFormat('dd')) }}
                                @endif
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

                <flux:button icon="chevron-right" size="sm" variant="ghost"
                    wire:click="step(1)" :disabled="$index === false || $index >= $days->count() - 1" />
            </div>
        </div>

        {{-- Matches for the selected day --}}
        <section class="space-y-3">
            @forelse ($this->dayMatches as $match)
                @if ($match->isLocked())
                    <x-vg.match-predictions
                        :match="$match"
                        :predictions="app(PredictionLockService::class)->visiblePredictions($match)" />
                @else
                    <x-vg.match-fixture :match="$match" :prediction="$match->predictions->first()" />
                @endif
            @empty
                <flux:card><flux:text>Geen wedstrijden op deze dag.</flux:text></flux:card>
            @endforelse
        </section>
    @endif
</div>
