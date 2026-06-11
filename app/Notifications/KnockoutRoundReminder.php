<?php

namespace App\Notifications;

use App\Support\KnockoutSchema;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class KnockoutRoundReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $stage,
        public int $leadHours,
        public int $openMatches,
        public Carbon $deadline,
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
        $label = KnockoutSchema::label($this->stage);
        $deadline = $this->deadline->timezone('Europe/Amsterdam')->isoFormat('dddd D MMMM, HH:mm');
        $matchWord = $this->openMatches === 1 ? 'wedstrijd' : 'wedstrijden';

        return (new MailMessage)
            ->subject("ValiGOAL: nog {$this->leadHours} uur om je {$label} in te vullen")
            ->greeting("Hoi {$notifiable->name},")
            ->line("De **{$label}** gaat bijna van start: de eerste wedstrijd begint op {$deadline}.")
            ->line("Je hebt nog **{$this->openMatches}** {$matchWord} openstaan. Vul je voorspellingen op tijd in, want elke wedstrijd sluit bij de eigen aftrap.")
            ->action('Nu voorspellen', route('predictions'))
            ->line('Succes met je voorspellingen!');
    }
}
