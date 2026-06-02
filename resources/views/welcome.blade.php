<!DOCTYPE html>
<html lang="nl" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ValiGOAL — Voorspellingen gevalideerd, GOALs gegarandeerd</title>
        <meta name="description" content="De WK 2026 voorspelpoule van Valicare. Voorspel wedstrijden, scoor punten en klim naar de top van het klassement.">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-pitch-950 text-white antialiased selection:bg-brand-500/30">
        {{-- Decorative glows --}}
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="vg-orb absolute -top-32 -left-24 h-[32rem] w-[32rem] rounded-full bg-brand-500/25 blur-[120px]"></div>
            <div class="vg-orb absolute top-1/3 -right-32 h-[32rem] w-[32rem] rounded-full bg-gold-400/15 blur-[130px]" style="animation-delay:-2s"></div>
            <div class="vg-orb absolute -bottom-40 left-1/3 h-[28rem] w-[28rem] rounded-full bg-brand-700/20 blur-[120px]" style="animation-delay:-4s"></div>
        </div>
        <canvas id="vg-grid" aria-hidden="true" class="pointer-events-none fixed inset-0 h-full w-full opacity-60"></canvas>

        {{-- Navbar --}}
        <header class="fixed inset-x-3 top-3 z-50 mx-auto max-w-7xl rounded-2xl border border-white/10 bg-pitch-900/60 px-4 py-3 backdrop-blur-xl sm:px-6">
            <nav class="flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand-500 text-white shadow-lg shadow-brand-500/40">
                        <x-app-logo-icon class="size-5" />
                    </span>
                    <span class="font-display text-xl tracking-tight">Vali<span class="text-gold-400">GOAL</span></span>
                </a>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="cursor-pointer rounded-xl bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition-colors duration-200 hover:bg-brand-400">
                            Naar dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="cursor-pointer rounded-xl px-4 py-2 text-sm font-semibold text-zinc-200 transition-colors duration-200 hover:bg-white/10 hover:text-white">
                            Inloggen
                        </a>
                        <a href="{{ route('register') }}" class="cursor-pointer rounded-xl bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition-colors duration-200 hover:bg-brand-400">
                            Meedoen
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="relative z-10">
            {{-- Hero --}}
            <section class="mx-auto flex min-h-screen max-w-7xl flex-col items-center justify-center px-5 pb-20 pt-32 text-center">
                <div class="mb-7 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-gold-400 px-5 py-1.5 text-xs font-bold uppercase tracking-[0.2em] text-pitch-950">
                    <span class="size-1.5 rounded-full bg-pitch-950"></span>
                    WK 2026 · Valicare-poule
                </div>
                <h1 class="font-display text-6xl font-bold leading-[0.92] tracking-tight sm:text-7xl lg:text-[7rem]">
                    <span class="block">Voorspel.</span>
                    <span class="block bg-gradient-to-r from-brand-400 via-brand-300 to-gold-400 bg-clip-text text-transparent">Scoor. Win.</span>
                </h1>

                <p class="mt-6 font-display text-xl font-semibold tracking-tight text-brand-300 sm:text-2xl">
                    Voorspellingen gevalideerd, GOALs gegarandeerd
                </p>

                <p class="mx-auto mt-5 max-w-2xl text-lg text-zinc-300 sm:text-xl">
                    De officiële WK-voorspelpoule van Valicare. Voorspel elke wedstrijd, raad de
                    <span class="font-semibold text-white">topscorer</span> en
                    <span class="font-semibold text-white">toernooiwinnaar</span>, en klim naar de top van het klassement.
                </p>

                <div class="mt-10 flex flex-col items-center gap-3 sm:flex-row">
                    @auth
                        <a href="{{ route('predictions') }}" class="group inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-brand-500 px-8 py-4 text-base font-bold text-white shadow-xl shadow-brand-500/40 transition-all duration-200 hover:bg-brand-400 hover:shadow-brand-500/60">
                            <x-icon-trophy class="size-5" /> Voorspellen
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="group inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-brand-500 px-8 py-4 text-base font-bold text-white shadow-xl shadow-brand-500/40 transition-all duration-200 hover:bg-brand-400 hover:shadow-brand-500/60">
                            <x-icon-trophy class="size-5" /> Meedoen
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex cursor-pointer items-center gap-2 rounded-2xl border-2 border-gold-400/40 bg-pitch-900/60 px-8 py-4 text-base font-bold text-white backdrop-blur-md transition-colors duration-200 hover:border-gold-400 hover:bg-pitch-800/60">
                            <x-icon-bolt class="size-5 text-gold-400" /> Inloggen
                        </a>
                    @endauth
                </div>

                {{-- Stat chips --}}
                <div class="mt-16 grid w-full max-w-3xl grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-stat-chip label="Deelnemers" :value="number_format($stats['players'], 0, ',', '.')">
                        <x-icon-users class="size-6" />
                    </x-stat-chip>
                    <x-stat-chip label="Wedstrijden" :value="number_format($stats['matches'], 0, ',', '.')">
                        <x-icon-ball class="size-6" />
                    </x-stat-chip>
                    <x-stat-chip label="Punten gescoord" :value="number_format($stats['points'], 0, ',', '.')">
                        <x-icon-chart class="size-6" />
                    </x-stat-chip>
                </div>
            </section>

            {{-- Features --}}
            <section class="mx-auto max-w-7xl px-5 py-24">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="font-display text-4xl font-bold tracking-tight sm:text-5xl">Hoe het werkt</h2>
                    <p class="mt-4 text-lg text-zinc-400">Drie manieren om punten te pakken. Eén klassement om te winnen.</p>
                </div>

                <div class="mt-14 grid gap-6 md:grid-cols-3">
                    <x-feature-card title="Voorspel wedstrijden" accent="brand">
                        <x-slot:icon><x-icon-ball class="size-6" /></x-slot:icon>
                        Vul vóór de aftrap je uitslag in. <strong class="text-white">5 punten</strong> voor de exacte score,
                        <strong class="text-white">3</strong> voor de juiste uitkomst en <strong class="text-white">+1</strong> voor het juiste doelsaldo.
                    </x-feature-card>
                    <x-feature-card title="Bonusvragen" accent="gold">
                        <x-slot:icon><x-icon-trophy class="size-6" /></x-slot:icon>
                        Raad de <strong class="text-white">winnaar (15)</strong>, de <strong class="text-white">finalist (10)</strong>
                        en de <strong class="text-white">topscorer (10)</strong>. Sluit bij de aftrap van het toernooi.
                    </x-feature-card>
                    <x-feature-card title="Klassement" accent="brand">
                        <x-slot:icon><x-icon-chart class="size-6" /></x-slot:icon>
                        Eén poule, één algemeen klassement. Gelijk? De meeste exacte uitslagen en juiste uitkomsten geven de doorslag.
                    </x-feature-card>
                </div>
            </section>

            {{-- CTA --}}
            <section class="mx-auto max-w-7xl px-5 pb-28">
                <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-brand-600 via-brand-700 to-pitch-900 px-8 py-16 text-center shadow-2xl sm:px-16">
                    <div aria-hidden="true" class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gold-400/20 blur-3xl"></div>
                    <h2 class="font-display text-4xl font-bold tracking-tight sm:text-5xl">Klaar om te scoren?</h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-brand-50/90">
                        Doe mee met je <span class="font-semibold text-white">@valicare.nl</span>-adres en zet je eerste voorspelling neer.
                    </p>
                    <div class="mt-8">
                        <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="inline-flex cursor-pointer items-center gap-2 rounded-2xl bg-gold-400 px-8 py-4 text-base font-bold text-pitch-950 shadow-xl shadow-gold-400/30 transition-colors duration-200 hover:bg-gold-300">
                            <x-icon-trophy class="size-5" /> {{ auth()->check() ? 'Naar dashboard' : 'Account aanmaken' }}
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="relative z-10 border-t border-white/10 py-8">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-5 text-sm text-zinc-500 sm:flex-row">
                <div class="flex items-center gap-2">
                    <x-app-logo-icon class="size-4 text-brand-500" />
                    <span>ValiGOAL — een initiatief van Valicare</span>
                </div>
                <span>&copy; {{ date('Y') }} Valicare. Alle rechten voorbehouden.</span>
            </div>
        </footer>

        <script>
            (function () {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                const canvas = document.getElementById('vg-grid');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const SPACING = 32, R = 1.4;
                let dots = [], w = 0, h = 0;
                const mouse = { x: null, y: null };

                function build() {
                    w = canvas.width = window.innerWidth;
                    h = canvas.height = window.innerHeight;
                    dots = [];
                    for (let x = SPACING / 2; x < w; x += SPACING) {
                        for (let y = SPACING / 2; y < h; y += SPACING) {
                            dots.push({ x, y, o: 0.15 + Math.random() * 0.25, s: 0.001 + Math.random() * 0.003 });
                        }
                    }
                }
                function frame() {
                    ctx.clearRect(0, 0, w, h);
                    for (const d of dots) {
                        d.o += d.s;
                        if (d.o > 0.4 || d.o < 0.15) d.s = -d.s;
                        let boost = 0, radius = R;
                        if (mouse.x !== null) {
                            const dx = d.x - mouse.x, dy = d.y - mouse.y, dist = Math.hypot(dx, dy);
                            if (dist < 130) { boost = Math.pow(1 - dist / 130, 2); radius = R + boost * 2.2; }
                        }
                        ctx.beginPath();
                        ctx.fillStyle = 'rgba(20,170,220,' + Math.min(1, d.o + boost * 0.6) + ')';
                        ctx.arc(d.x, d.y, radius, 0, Math.PI * 2);
                        ctx.fill();
                    }
                    requestAnimationFrame(frame);
                }
                window.addEventListener('resize', build);
                window.addEventListener('mousemove', (e) => { mouse.x = e.clientX; mouse.y = e.clientY; }, { passive: true });
                build();
                frame();
            })();
        </script>
    </body>
</html>
