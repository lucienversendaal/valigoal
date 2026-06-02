@props([
    'team' => null,
    'reverse' => false,
])

<div @class(['flex items-center gap-2', 'flex-row-reverse' => $reverse])>
    @if ($team?->crest_url)
        <img src="{{ $team->crest_url }}" alt="{{ $team->name }}" class="size-6 object-contain" loading="lazy" />
    @else
        <span class="flex size-6 items-center justify-center rounded-full bg-zinc-200 text-[10px] font-bold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
            {{ $team?->tla ?? '?' }}
        </span>
    @endif
    <span class="text-sm font-semibold">{{ $team?->name ?? 'TBD' }}</span>
</div>
