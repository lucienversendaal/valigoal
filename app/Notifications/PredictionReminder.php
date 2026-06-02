<?php

namespace App\Notifications;

use App\Models\GameMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PredictionReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public GameMatch $match,
        public int $leadHours,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $home = $this->match->homeTeam?->name ?? 'TBD';
        $away = $this->match->awayTeam?->name ?? 'TBD';
        $kickoff = $this->match->kickoff_at?->timezone('Europe/Amsterdam')->format('d-m-Y H:i');

        return (new MailMessage)
            ->subject("ValiGOAL: nog {$this->leadHours} uur om {$home} - {$away} te voorspellen")
            ->greeting("Hoi {$notifiable->name},")
            ->line("Je hebt nog geen voorspelling ingevuld voor **{$home} - {$away}**.")
            ->line("Aftrap is om {$kickoff} — daarna sluit de wedstrijd en kun je geen punten meer scoren.")
            ->action('Nu voorspellen', route('predictions'))
            ->line('Succes met je voorspelling!');
    }
}
