<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscription $subscription, public int $daysLeft) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $endsAt = $this->subscription->ends_at->translatedFormat('d F Y');
        $urgency = $this->daysLeft === 1 ? 'demain' : "dans {$this->daysLeft} jours";

        return (new MailMessage)
            ->subject("Votre abonnement expire {$urgency} — ".config('app.name'))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre abonnement **{$this->subscription->plan->name}** expire le **{$endsAt}** ({$urgency}).")
            ->line('Pour éviter toute interruption de service, renouvelez votre abonnement dès maintenant.')
            ->action('Renouveler mon abonnement', route('subscription.index'))
            ->line('Si vous avez déjà effectué un paiement, notre équipe le validera dans les plus brefs délais.')
            ->salutation("L'équipe ".config('app.name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_expiring',
            'days_left' => $this->daysLeft,
            'ends_at' => $this->subscription->ends_at->toDateString(),
            'plan' => $this->subscription->plan->name,
        ];
    }
}
