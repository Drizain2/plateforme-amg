<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentValidated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment, public Subscription $subscription) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
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
