<?php

namespace App\Notifications;

use App\Support\KnockoutSchema;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class KnockoutRoundOpen extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $stage,
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

        return (new MailMessage)
            ->subject("ValiGOAL: de {$label} is te voorspellen!")
            ->greeting("Hoi {$notifiable->name},")
            ->line("De vorige ronde is gespeeld — je kunt nu je voorspellingen voor de **{$label}** invullen.")
            ->line("De eerste wedstrijd begint op {$deadline}. Elke wedstrijd sluit bij de eigen aftrap, dus vul ze op tijd in.")
            ->action('Nu voorspellen', route('predictions'))
            ->line('Succes!');
    }
}
