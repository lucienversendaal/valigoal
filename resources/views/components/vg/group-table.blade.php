@props([
    'rows' => [],
    'predicted' => false,
    'empty' => 'Geen gegevens.',
])

<flux:card class="!p-0 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs uppercase tracking-wide text-zinc-500">
                <th class="px-3 py-2 font-medium">#</th>
                <th class="px-3 py-2 font-medium">Land</th>
                <th class="px-2 py-2 text-right font-medium" title="Gespeeld">G</th>
                <th class="px-2 py-2 text-right font-medium" title="Doelsaldo">DS</th>
                <th class="px-3 py-2 text-right font-medium">Ptn</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php($position = (int) data_get($row, 'position'))
                @php($gd = (int) data_get($row, 'goal_difference'))
                <tr @class([
                    'border-t border-zinc-100 dark:border-zinc-800',
                    'bg-gold-400/10' => $predicted && $position <= 2,
                    'bg-brand-50/60 dark:bg-brand-500/10' => ! $predicted && $position <= 2,
                ])>
                    <td class="px-3 py-2.5 font-display font-bold text-zinc-400">{{ $position }}</td>
                    <td class="px-3 py-2.5"><x-vg.team-badge :team="data_get($row, 'team')" /></td>
                    <td class="px-2 py-2.5 text-right tabular-nums text-zinc-500">{{ data_get($row, 'played') }}</td>
                    <td class="px-2 py-2.5 text-right tabular-nums text-zinc-500">{{ $gd > 0 ? '+' : '' }}{{ $gd }}</td>
                    <td @class([
                        'px-3 py-2.5 text-right font-display font-bold',
                        'text-gold-600 dark:text-gold-400' => $predicted,
                        'text-brand-600 dark:text-brand-400' => ! $predicted,
                    ])>{{ data_get($row, 'points') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-3 py-4 text-center text-sm text-zinc-500">{{ $empty }}</td></tr>
            @endforelse
        </tbody>
    </table>
</flux:card>
