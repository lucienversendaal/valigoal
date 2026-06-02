@props([
    'sidebar' => false,
    'href' => null,
])

<a
    href="{{ $href ?? route('home') }}"
    {{ $attributes->class('flex items-center gap-2.5 me-4 h-10') }}
>
    <span class="flex aspect-square size-9 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white shadow-sm shadow-brand-500/30">
        <x-app-logo-icon class="size-5" />
    </span>
    <span class="font-display text-xl leading-none tracking-tight">
        <span class="text-zinc-900 dark:text-white">Vali</span><span class="text-gold-500">GOAL</span>
    </span>
</a>
