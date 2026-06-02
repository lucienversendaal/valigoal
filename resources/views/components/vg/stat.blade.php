@props([
    'icon' => 'bolt',
    'label' => '',
    'value' => '',
    'accent' => 'brand',
])

@php
    $ring = $accent === 'gold'
        ? 'bg-gold-400/15 text-gold-500 dark:text-gold-300'
        : 'bg-brand-500/15 text-brand-600 dark:text-brand-300';
@endphp

<flux:card class="flex items-center gap-4 !p-4">
    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $ring }}">
        <flux:icon :icon="$icon" variant="solid" class="size-5" />
    </span>
    <div class="min-w-0">
        <div class="truncate text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
        <div class="font-display text-2xl font-bold leading-tight">{{ $value }}</div>
    </div>
</flux:card>
