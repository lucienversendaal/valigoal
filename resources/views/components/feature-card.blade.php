@props([
    'title' => '',
    'accent' => 'brand',
])

@php
    $accentClasses = $accent === 'gold'
        ? 'bg-gold-400/15 text-gold-300 ring-gold-400/30'
        : 'bg-brand-500/15 text-brand-300 ring-brand-500/30';
@endphp

<div class="group relative overflow-hidden rounded-3xl border border-white/10 bg-pitch-900/50 p-7 backdrop-blur-md transition-all duration-200 hover:border-white/20 hover:bg-pitch-800/50">
    <div class="mb-5 flex size-12 items-center justify-center rounded-2xl ring-1 {{ $accentClasses }}">
        {{ $icon }}
    </div>
    <h3 class="font-display text-2xl font-bold tracking-tight text-white">{{ $title }}</h3>
    <p class="mt-3 text-zinc-400">{{ $slot }}</p>
</div>
