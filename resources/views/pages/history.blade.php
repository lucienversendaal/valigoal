<?php

use App\Models\HistoricalFact;
use App\Models\Tournament;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Historie')] class extends Component {
    #[Computed]
    public function winners()
    {
        return HistoricalFact::where('type', 'previous_winner')->orderByDesc('year')->get();
    }

    #[Computed]
    public function scorers()
    {
        return HistoricalFact::where('type', 'all_time_top_scorer')->orderBy('sort_order')->get();
    }

    #[Computed]
    public function recentScorers()
    {
        $tournament = Tournament::current();
        $liveYear = (int) ($tournament?->season ?: ($tournament?->starts_at?->year ?? now()->year));

        return HistoricalFact::where('type', 'wc_top_scorer')
            ->where('year', '!=', $liveYear)
            ->orderByDesc('year')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('year');
    }

    #[Computed]
    public function memorable()
    {
        return HistoricalFact::where('type', 'memorable_match')->orderBy('sort_order')->get();
    }
}; ?>

<div class="mx-auto w-full max-w-4xl space-y-10">
        <div>
            <flux:heading size="xl" class="font-display !text-3xl">WK Historie</flux:heading>
            <flux:text class="mt-1">Een blik op de geschiedenis van het grootste voetbaltoernooi ter wereld.</flux:text>
        </div>

        {{-- Previous winners --}}
        <section class="space-y-4">
            <flux:heading size="lg" class="font-display">Vorige winnaars</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->winners as $w)
                    <flux:card class="relative overflow-hidden">
                        <div aria-hidden="true" class="absolute -right-6 -top-6 text-gold-400/20"><x-icon-trophy class="size-24" /></div>
                        <div class="relative">
                            <div class="font-display text-3xl font-bold text-gold-500">{{ $w->year }}</div>
                            <div class="mt-1 text-lg font-semibold">{{ $w->title }}</div>
                            <div class="text-sm text-zinc-500">{{ $w->subtitle }}</div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        </section>

        {{-- Top scorers: recent Golden Boots (full width) above all-time --}}
        <section class="space-y-4">
            <flux:heading size="lg" class="font-display">Topscorers</flux:heading>

            @foreach ($this->recentScorers as $year => $players)
                <div class="space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-zinc-500">WK {{ $year }} · Golden Boot</div>
                    <x-vg.scorer-list :rows="$players" :goldFirst="true" />
                </div>
            @endforeach

            <div class="space-y-2">
                <div class="text-xs font-bold uppercase tracking-wider text-zinc-500">Aller tijden</div>
                <x-vg.scorer-list :rows="$this->scorers" :goldFirst="false" />
            </div>
        </section>

        {{-- Memorable matches --}}
        <section class="space-y-4">
            <flux:heading size="lg" class="font-display">Memorabele wedstrijden</flux:heading>
            <div class="space-y-3">
                @foreach ($this->memorable as $m)
                    <flux:card class="flex items-start gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-500/15 text-brand-600 dark:text-brand-300"><x-icon-ball class="size-5" /></span>
                        <div>
                            <div class="font-display text-lg font-bold">{{ $m->title }}</div>
                            <div class="text-sm font-medium text-zinc-500">{{ $m->subtitle }}</div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $m->body }}</p>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        </section>
    </div>
