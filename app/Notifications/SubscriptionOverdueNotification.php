<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public int $daysOverdue,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->subscription->plan->name;
        $shopName = $this->subscription->shop->name;

        if ($this->daysOverdue >= 7) {
            return (new MailMessage)
                ->subject('Accès suspendu — '.config('app.name'))
                ->greeting("Bonjour {$notifiable->name},")
                ->line("L'accès de **{$shopName}** à ".config('app.name')." a été **suspendu** suite au non-renouvellement de l'abonnement **{$planName}**.")
                ->line('Pour réactiver votre accès, renouvelez votre abonnement et contactez notre équipe une fois le paiement effectué.')
                ->action('Renouveler maintenant', route('subscription.index'))
                ->line('Si vous pensez qu\'il s\'agit d\'une erreur ou si vous avez déjà effectué un paiement, répondez à cet email.')
                ->salutation("L'équipe ".config('app.name'));
        }

        return (new MailMessage)
            ->subject("Abonnement en retard de {$this->daysOverdue} jours — ".config('app.name'))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("L'abonnement **{$planName}** de **{$shopName}** a expiré il y a **{$this->daysOverdue} jours**.")
            ->line('Votre accès est actuellement bloqué. Renouvelez votre abonnement pour le récupérer immédiatement.')
            ->action('Renouveler mon abonnement', route('subscription.index'))
            ->line('**Sans paiement dans les prochains jours, votre compte sera suspendu.**')
            ->salutation("L'équipe ".config('app.name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_overdue',
            'days_overdue' => $this->daysOverdue,
            'plan' => $this->subscription->plan->name,
            'ended_at' => $this->subscription->ends_at->toDateString(),
        ];
    }
}
