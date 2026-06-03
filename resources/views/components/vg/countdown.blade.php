@props([
    'until' => null,
    'label' => 'Sluit over',
    'expired' => 'Gesloten',
])

@if ($until)
    <div
        wire:ignore
        x-data="{
            target: {{ $until->getTimestamp() * 1000 }},
            now: Date.now(),
            init() { setInterval(() => (this.now = Date.now()), 1000) },
            get diff() { return Math.max(0, this.target - this.now) },
            get done() { return this.diff <= 0 },
            get d() { return Math.floor(this.diff / 86400000) },
            get h() { return Math.floor((this.diff % 86400000) / 3600000) },
            get m() { return Math.floor((this.diff % 3600000) / 60000) },
            get s() { return Math.floor((this.diff % 60000) / 1000) },
            pad(n) { return String(n).padStart(2, '0') },
        }"
        {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}
    >
        <template x-if="done">
            <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-zinc-500">
                <flux:icon.lock-closed class="size-4" /> {{ $expired }}
            </span>
        </template>

        <template x-if="!done">
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ $label }}</span>
                <div class="flex items-stretch gap-1 font-display tabular-nums">
                    <template x-if="d > 0">
                        <div class="flex flex-col items-center">
                            <span class="min-w-9 rounded-lg bg-zinc-900 px-2 py-1 text-center text-lg font-bold text-white dark:bg-white dark:text-zinc-900" x-text="d"></span>
                            <span class="mt-0.5 text-[10px] uppercase text-zinc-400">dag</span>
                        </div>
                    </template>
                    <div class="flex flex-col items-center">
                        <span class="min-w-9 rounded-lg bg-zinc-900 px-2 py-1 text-center text-lg font-bold text-white dark:bg-white dark:text-zinc-900" x-text="pad(h)"></span>
                        <span class="mt-0.5 text-[10px] uppercase text-zinc-400">uur</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="min-w-9 rounded-lg bg-zinc-900 px-2 py-1 text-center text-lg font-bold text-white dark:bg-white dark:text-zinc-900" x-text="pad(m)"></span>
                        <span class="mt-0.5 text-[10px] uppercase text-zinc-400">min</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="min-w-9 rounded-lg bg-brand-500 px-2 py-1 text-center text-lg font-bold text-white" x-text="pad(s)"></span>
                        <span class="mt-0.5 text-[10px] uppercase text-zinc-400">sec</span>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
