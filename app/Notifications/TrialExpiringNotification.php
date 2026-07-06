<?php

namespace App\Notifications;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Shop $shop, public int $daysLeft) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysLeft === 1 ? 'demain' : "dans {$this->daysLeft} jours";
        $endsAt = $this->shop->trial_ends_at->translatedFormat('d F Y');

        return (new MailMessage)
            ->subject("Votre essai gratuit expire {$urgency} — ".config('app.name'))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre période d'essai gratuit de **{$this->shop->name}** expire le **{$endsAt}** ({$urgency}).")
            ->line('Pour continuer à utiliser toutes les fonctionnalités sans interruption, choisissez un plan adapté à votre atelier.')
            ->action('Choisir mon plan', route('subscription.index'))
            ->line('Si vous avez des questions, consultez notre page de tarification ou contactez notre support.')
            ->salutation("L'équipe ".config('app.name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_expiring',
            'days_left' => $this->daysLeft,
            'ends_at' => $this->shop->trial_ends_at->toDateString(),
        ];
    }
}
