<?php

use App\Enums\BonusType;
use App\Models\BonusPrediction;
use App\Models\Team;
use App\Models\Tournament;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Bonusvragen')] class extends Component {
    public ?int $winnerTeamId = null;
    public ?int $finalistTeamId = null;
    public string $topScorer = '';

    public function mount(): void
    {
        foreach (Auth::user()->bonusPredictions()->where('tournament_id', $this->tournament?->id)->get() as $bonus) {
            match ($bonus->type) {
                BonusType::Winner => $this->winnerTeamId = $bonus->team_id,
                BonusType::Finalist => $this->finalistTeamId = $bonus->team_id,
                BonusType::TopScorer => $this->topScorer = (string) $bonus->player_name,
            };
        }
    }

    #[Computed]
    public function tournament(): ?Tournament
    {
        return Tournament::current();
    }

    #[Computed]
    public function locked(): bool
    {
        return $this->tournament?->bonusesAreLocked() ?? true;
    }

    #[Computed]
    public function teams()
    {
        return $this->tournament
            ? Team::where('tournament_id', $this->tournament->id)->orderBy('name')->get()
            : collect();
    }

    public function save(): void
    {
        if ($this->locked) {
            Flux::toast(variant: 'danger', text: 'De bonusvragen zijn gesloten.');

            return;
        }

        $this->validate([
            'winnerTeamId' => ['nullable', 'integer', 'exists:teams,id'],
            'finalistTeamId' => ['nullable', 'integer', 'exists:teams,id'],
            'topScorer' => ['nullable', 'string', 'max:255'],
        ]);

        $tournament = $this->tournament;

        $this->upsert(BonusType::Winner, ['team_id' => $this->winnerTeamId]);
        $this->upsert(BonusType::Finalist, ['team_id' => $this->finalistTeamId]);
        $this->upsert(BonusType::TopScorer, ['player_name' => $this->topScorer ?: null]);

        Flux::toast(variant: 'success', text: 'Bonusvragen opgeslagen!');
    }

    protected function upsert(BonusType $type, array $values): void
    {
        BonusPrediction::updateOrCreate(
            ['user_id' => Auth::id(), 'tournament_id' => $this->tournament->id, 'type' => $type->value],
            $values,
        );
    }
}; ?>

<div class="mx-auto w-full max-w-2xl space-y-6">
        <div>
            <flux:heading size="xl" class="font-display !text-3xl">Bonusvragen</flux:heading>
            <flux:text class="mt-1">Extra punten voor de grote voorspellingen. Deze sluiten bij de aftrap van het toernooi.</flux:text>
        </div>

        @if ($this->locked)
            <flux:callout variant="warning" icon="lock-closed" heading="De bonusvragen zijn gesloten">
                Het toernooi is begonnen. Je inzendingen staan vast en worden na afloop verrekend.
            </flux:callout>
        @else
            <flux:callout variant="secondary" icon="clock">
                Nog open tot {{ $this->tournament?->starts_at?->timezone('Europe/Amsterdam')->isoFormat('D MMMM, HH:mm') }}.
            </flux:callout>
        @endif

        <form wire:submit="save" class="space-y-5">
            <flux:card class="space-y-5">
                <div class="flex items-start gap-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-gold-400/15 text-gold-500"><x-icon-trophy class="size-5" /></span>
                    <div class="flex-1">
                        <flux:select wire:model="winnerTeamId" :label="__('Toernooiwinnaar (15 punten)')" :disabled="$this->locked" placeholder="Kies een land">
                            @foreach ($this->teams as $team)
                                <flux:select.option :value="$team->id">{{ $team->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <flux:separator />

                <div class="flex items-start gap-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-500/15 text-brand-600 dark:text-brand-300"><x-icon-ball class="size-5" /></span>
                    <div class="flex-1">
                        <flux:select wire:model="finalistTeamId" :label="__('Finalist (10 punten)')" :disabled="$this->locked" placeholder="Kies een land">
                            @foreach ($this->teams as $team)
                                <flux:select.option :value="$team->id">{{ $team->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <flux:separator />

                <div class="flex items-start gap-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-500/15 text-brand-600 dark:text-brand-300"><x-icon-bolt class="size-5" /></span>
                    <div class="flex-1">
                        <flux:input wire:model="topScorer" :label="__('Topscorer (10 punten)')" :disabled="$this->locked" placeholder="Naam van de speler" />
                    </div>
                </div>
            </flux:card>

            @unless ($this->locked)
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" icon="check">Opslaan</flux:button>
                </div>
            @endunless
        </form>
    </div>
