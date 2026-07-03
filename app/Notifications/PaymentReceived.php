<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
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
        $period = $this->payment->billing_period->value === 'monthly' ? 'mensuel' : 'annuel';

        return (new MailMessage)
            ->subject("Nouveau paiement reçu — {$this->payment->shop->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("L'atelier **{$this->payment->shop->name}** a soumis un paiement de **{$amount} {$this->payment->currency}** pour un abonnement {$period}.")
            ->line("Référence : `{$this->payment->reference}`")
            ->action('Valider le paiement', route('admin.payments.index'))
            ->salutation("L'équipe ".config('app.name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
            'shop' => $this->payment->shop->name,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency,
            'period' => $this->payment->billing_period->value,
        ];
    }
}
