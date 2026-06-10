<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Ticket $ticket)
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
        $status = $this->ticket->status->label();
        $url    = route('track', $this->ticket->tracking_token);

        return (new MailMessage)
            ->subject("Votre réparation — {$this->ticket->reference} : {$status}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le statut de votre réparation a été mis à jour.")
            ->line("**Appareil :** {$this->ticket->device->full_name}")
            ->line("**Nouveau statut :** {$status}")
            ->when($this->ticket->estimated_return_date, fn($m) =>
                $m->line("**Date de retour estimée :** {$this->ticket->estimated_return_date->format('d/m/Y')}")
            )
            ->action('Suivre ma réparation', $url)
            ->salutation("L'équipe {$this->ticket->shop->name}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id'     => $this->ticket->id,
            'reference'     => $this->ticket->reference,
            'status'        => $this->ticket->status->value,
            'status_label'  => $this->ticket->status->label(),
        ];
    }
}
