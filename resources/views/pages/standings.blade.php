<?php

use App\Models\Standing;
use App\Models\Tournament;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Poules')] class extends Component {
    #[Computed]
    public function tournament(): ?Tournament
    {
        return Tournament::current();
    }

    #[Computed]
    public function groups()
    {
        if (! $this->tournament) {
            return collect();
        }

        return Standing::query()
            ->where('tournament_id', $this->tournament->id)
            ->with('team')
            ->orderBy('group')
            ->orderBy('position')
            ->get()
            ->groupBy('group');
    }
}; ?>

<div class="mx-auto w-full max-w-5xl space-y-6">
    <div>
        <flux:heading size="xl" class="font-display !text-3xl">Poules &amp; standen</flux:heading>
        <flux:text class="mt-1">De groepsindeling en tussenstanden van {{ $this->tournament?->name ?? 'het toernooi' }}.</flux:text>
    </div>

    @forelse ($this->groups as $group => $rows)
        <flux:card class="!p-0 overflow-hidden">
            @php($letter = $rows->first()?->groupLetter())
            <a href="{{ $letter ? route('group', ['group' => $letter]) : '#' }}" wire:navigate
               class="flex items-center justify-between border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 transition-colors hover:bg-brand-50 dark:border-zinc-700 dark:bg-zinc-800/60 dark:hover:bg-brand-500/10">
                <span class="font-display text-lg font-bold">{{ \Illuminate\Support\Str::headline(strtolower($group ?? 'Groep')) }}</span>
                <flux:icon icon="arrow-right" class="size-4 text-zinc-400" />
            </a>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-zinc-500">
                            <th class="px-4 py-2 font-medium">#</th>
                            <th class="px-4 py-2 font-medium">Land</th>
                            <th class="px-3 py-2 text-right font-medium" title="Gespeeld">G</th>
                            <th class="px-3 py-2 text-right font-medium" title="Winst">W</th>
                            <th class="px-3 py-2 text-right font-medium" title="Gelijk">GL</th>
                            <th class="px-3 py-2 text-right font-medium" title="Verlies">V</th>
                            <th class="px-3 py-2 text-right font-medium" title="Doelsaldo">DS</th>
                            <th class="px-4 py-2 text-right font-medium">Ptn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr @class([
                                'border-t border-zinc-100 dark:border-zinc-800',
                                'bg-brand-50/60 dark:bg-brand-500/10' => $row->position <= 2,
                            ])>
                                <td class="px-4 py-2.5 font-display font-bold text-zinc-400">{{ $row->position }}</td>
                                <td class="px-4 py-2.5">
                                    <x-vg.team-badge :team="$row->team" />
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-zinc-500">{{ $row->played }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-zinc-500">{{ $row->won }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-zinc-500">{{ $row->draw }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-zinc-500">{{ $row->lost }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-zinc-500">{{ $row->goal_difference > 0 ? '+' : '' }}{{ $row->goal_difference }}</td>
                                <td class="px-4 py-2.5 text-right font-display font-bold text-brand-600 dark:text-brand-400">{{ $row->points }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @empty
        <flux:card>
            <flux:text>Er zijn nog geen standen beschikbaar. Vraag de beheerder om een synchronisatie uit te voeren.</flux:text>
        </flux:card>
    @endforelse
</div>
