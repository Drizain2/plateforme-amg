<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->payment->amount, 0, ',', ' ');

        return (new MailMessage)
            ->subject('Paiement rejeté — action requise')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre paiement de **{$amount} {$this->payment->currency}** (réf. `{$this->payment->reference}`) n'a pas pu être validé.")
            ->line("Motif : {$this->payment->rejected_reason}")
            ->line('Veuillez soumettre un nouveau paiement ou contacter notre support si vous pensez qu\'il s\'agit d\'une erreur.')
            ->action('Gérer mon abonnement', route('subscription.index'))
            ->salutation("L'équipe ".config('app.name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_rejected',
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency,
            'rejected_reason' => $this->payment->rejected_reason,
        ];
    }
}
