@props([
    'rows' => [],
    'goldFirst' => false,
    'empty' => 'Nog geen gegevens.',
])

<flux:card class="!p-0 overflow-hidden">
    @forelse ($rows as $i => $s)
        <div @class(['flex items-center justify-between px-4 py-2.5', 'border-t border-zinc-100 dark:border-zinc-800' => $i > 0])>
            <div class="flex items-center gap-3">
                <span @class([
                    'flex size-7 items-center justify-center rounded-full font-display text-sm font-bold',
                    'bg-gold-400 text-pitch-950' => $goldFirst && $i === 0,
                    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $goldFirst && $i > 0,
                    'bg-brand-500/15 text-brand-600 dark:text-brand-300' => ! $goldFirst,
                ])>{{ $i + 1 }}</span>
                <div>
                    <div class="font-semibold">{{ $s->title }}</div>
                    <div class="text-xs text-zinc-500">{{ $s->subtitle }}</div>
                </div>
            </div>
            <div class="font-display text-lg font-bold text-brand-600 dark:text-brand-400">{{ data_get($s->meta, 'goals') }}<span class="ml-1 text-xs font-normal text-zinc-400">goals</span></div>
        </div>
    @empty
        <div class="px-4 py-6 text-center text-sm text-zinc-500">{{ $empty }}</div>
    @endforelse
</flux:card>
