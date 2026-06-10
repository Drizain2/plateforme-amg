<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceSent extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Invoice $invoice)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $pdfUrl = \URL::temporarySignedRoute('invoices.pdf.public', now()->addDays(7), ['invoice' => $this->invoice->id]);

        return (new MailMessage)
            ->subject("Votre facture {$this->invoice->number}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Veuillez trouver ci-joint votre facture.')
            ->line("**Numéro :** {$this->invoice->number}")
            ->line('**Montant TTC :** '.number_format($this->invoice->total_ttc, 2, ',', ' ').' €')
            ->when(
                $this->invoice->due_at,
                fn ($m) => $m->line("**Échéance :** {$this->invoice->due_at->format('d/m/Y')}")
            )
            ->action('Télécharger la facture', $pdfUrl)
            ->salutation("L'équipe {$this->invoice->shop->name}");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
