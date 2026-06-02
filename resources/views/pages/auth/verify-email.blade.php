<x-layouts::auth :title="__('Verifieer je e-mailadres')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Verifieer je e-mailadres')"
            :description="__('Bevestig je @valicare.nl-adres via de link die we je net hebben gemaild. Nog niets ontvangen? Vraag hieronder een nieuwe aan.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if (session('status') == 'verification-link-sent')
            <flux:callout variant="success" icon="check-circle">
                {{ __('Er is een nieuwe verificatielink verstuurd naar je e-mailadres.') }}
            </flux:callout>
        @endif

        <div class="flex flex-col items-center justify-between gap-4">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Verstuur opnieuw') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:button type="submit" variant="ghost" class="w-full">
                    {{ __('Uitloggen') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
