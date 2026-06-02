<?php

use App\Services\Scoring\LeaderboardService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Klassement')] class extends Component {
    #[Computed]
    public function standings()
    {
        return app(LeaderboardService::class)->standings();
    }

    #[Computed]
    public function podium()
    {
        return $this->standings->take(3);
    }
}; ?>

<div class="mx-auto w-full max-w-4xl space-y-8">
        <div>
            <flux:heading size="xl" class="font-display !text-3xl">Klassement</flux:heading>
            <flux:text class="mt-1">Eén poule, één algemeen klassement. Bij gelijk: meeste exacte uitslagen, dan juiste uitkomsten.</flux:text>
        </div>

        {{-- Podium --}}
        @if ($this->podium->count() === 3)
            @php($order = [$this->podium[1], $this->podium[0], $this->podium[2]])
            <div class="grid grid-cols-3 items-end gap-3 sm:gap-6">
                @foreach ($order as $row)
                    @php($rank = $row['rank'])
                    <div @class([
                        'relative flex flex-col items-center rounded-2xl border p-4 text-center',
                        'border-gold-400/60 bg-gradient-to-b from-gold-400/20 to-transparent pb-8' => $rank === 1,
                        'border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/40' => $rank === 2,
                        'border-amber-700/40 bg-amber-700/10' => $rank === 3,
                        'mt-6' => $rank !== 1,
                    ])>
                        <span @class([
                            'flex size-10 items-center justify-center rounded-full font-display text-lg font-bold',
                            'bg-gold-400 text-pitch-950' => $rank === 1,
                            'bg-zinc-300 text-zinc-700 dark:bg-zinc-600 dark:text-white' => $rank === 2,
                            'bg-amber-700 text-white' => $rank === 3,
                        ])>{{ $rank }}</span>
                        <div class="mt-3 truncate text-sm font-semibold">{{ $row['name'] }}</div>
                        <div class="font-display text-3xl font-bold text-brand-600 dark:text-brand-400">{{ $row['total_points'] }}</div>
                        <div class="text-xs text-zinc-500">{{ $row['exact_count'] }} exact</div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Full table --}}
        <flux:card class="!p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700">
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Deelnemer</th>
                            <th class="px-4 py-3 text-right font-medium">Wedstrijd</th>
                            <th class="px-4 py-3 text-right font-medium">Bonus</th>
                            <th class="px-4 py-3 text-right font-medium">Exact</th>
                            <th class="px-4 py-3 text-right font-medium">Totaal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->standings as $row)
                            <tr @class([
                                'border-b border-zinc-100 dark:border-zinc-800',
                                'bg-brand-50/70 dark:bg-brand-500/10' => $row['user']->id === auth()->id(),
                            ])>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'font-display font-bold',
                                        'text-gold-500' => $row['rank'] === 1,
                                        'text-zinc-400' => $row['rank'] > 1,
                                    ])>{{ $row['rank'] }}</span>
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    {{ $row['name'] }}
                                    @if ($row['user']->id === auth()->id())
                                        <flux:badge color="cyan" size="sm" class="ml-1">jij</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-zinc-500">{{ $row['match_points'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-zinc-500">{{ $row['bonus_points'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-zinc-500">{{ $row['exact_count'] }}</td>
                                <td class="px-4 py-3 text-right font-display text-base font-bold text-brand-600 dark:text-brand-400">{{ $row['total_points'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    </div>
