<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Doe mee met ValiGOAL')" :description="__('Maak een account met je @valicare.nl-adres')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Volledige naam')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Voor- en achternaam')"
                :description="__('Je volledige naam verschijnt in het klassement.')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('E-mailadres')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="naam@valicare.nl"
                :description="__('Registratie is alleen mogelijk met een @valicare.nl adres.')"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Wachtwoord')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Wachtwoord')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Bevestig wachtwoord')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Bevestig wachtwoord')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Account aanmaken') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Heb je al een account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Inloggen') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
