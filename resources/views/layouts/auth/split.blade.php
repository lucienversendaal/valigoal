<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="relative hidden h-full flex-col overflow-hidden p-10 text-white lg:flex">
                <div class="absolute inset-0 bg-gradient-to-br from-brand-600 via-brand-700 to-pitch-950"></div>
                <div aria-hidden="true" class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-gold-400/20 blur-3xl"></div>
                <div aria-hidden="true" class="absolute -bottom-24 -left-10 h-72 w-72 rounded-full bg-brand-400/20 blur-3xl"></div>

                <a href="{{ route('home') }}" class="relative z-20 flex items-center gap-2.5 text-lg font-medium" wire:navigate>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-white/15 backdrop-blur">
                        <x-app-logo-icon class="size-6 text-white" />
                    </span>
                    <span class="font-display text-2xl">Vali<span class="text-gold-300">GOAL</span></span>
                </a>

                <div class="relative z-20 mt-auto">
                    <blockquote class="space-y-3">
                        <flux:heading size="xl" class="!text-3xl !leading-tight text-white font-display">
                            Voorspellingen gevalideerd,<br>GOALs gegarandeerd.
                        </flux:heading>
                        <footer class="text-brand-100/80">De WK 2026 voorspelpoule van Valicare.</footer>
                    </blockquote>
                </div>
            </div>
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                        </span>

                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
