<?php

namespace App\Notifications;

use App\Models\StockDepot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public StockDepot $stockDepot) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('stock.parts.index', ['critical' => '1']);

        return (new MailMessage)
            ->subject("⚠️ Stock critique — {$this->stockDepot->part->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une pièce est en dessous du seuil critique.")
            ->line("**Pièce :** {$this->stockDepot->part->name}")
            ->line("**Dépôt :** {$this->stockDepot->depot->name}")
            ->line("**Stock actuel :** {$this->stockDepot->quantity} / Seuil : {$this->stockDepot->alert_quantity}")
            ->action('Voir le stock critique', $url)
            ->salutation("SAV Platform");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
     public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'low_stock',
            'part_id'       => $this->stockDepot->part->id,
            'part_name'     => $this->stockDepot->part->name,
            'quantity'      => $this->stockDepot->quantity,
            'min_threshold' => $this->stockDepot->alert_quantity,
            'depot_name'    => $this->stockDepot->depot->name,
        ];
    }
}
