@props([
    'label' => '',
    'value' => '',
])

<div class="group relative">
    <div aria-hidden="true" class="absolute inset-0 rounded-2xl bg-gradient-to-r from-brand-500/20 to-gold-400/20 blur-xl transition-all duration-300 group-hover:blur-2xl"></div>
    <div class="relative flex items-center gap-4 rounded-2xl border border-brand-500/30 bg-pitch-900/60 px-6 py-4 backdrop-blur-md transition-colors duration-300 hover:border-brand-400/60">
        <div class="text-brand-400">{{ $slot }}</div>
        <div class="text-left">
            <div class="text-xs uppercase tracking-wider text-zinc-400">{{ $label }}</div>
            <div class="font-display text-2xl font-bold text-white">{{ $value }}</div>
        </div>
    </div>
</div>
