<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentValidated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment, public Subscription $subscription) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->payment->amount, 0, ',', ' ');
        $endsAt = $this->subscription->ends_at->translatedFormat('d F Y');

        return (new MailMessage)
            ->subject('Paiement validé — votre abonnement est actif')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre paiement de **{$amount} {$this->payment->currency}** (réf. `{$this->payment->reference}`) a bien été validé.")
            ->line("Votre abonnement est actif jusqu'au **{$endsAt}**.")
            ->action('Accéder à mon espace', route('dashboard'))
            ->salutation("L'équipe ".config('app.name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_validated',
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency,
            'ends_at' => $this->subscription->ends_at->toDateString(),
        ];
    }
}
