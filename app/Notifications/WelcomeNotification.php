<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $shopName, public string $trialEndsAt) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur '.config('app.name')." — {$this->shopName}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre atelier **{$this->shopName}** a bien été créé sur ".config('app.name').'.')
            ->line("Vous disposez d'un essai gratuit de 14 jours jusqu'au **{$this->trialEndsAt}**, sans engagement.")
            ->action('Accéder à mon espace', route('dashboard'))
            ->line('Pendant la période d\'essai, vous avez accès à toutes les fonctionnalités de la plateforme.')
            ->line("À l'issue de l'essai, choisissez un plan adapté à votre atelier pour continuer.")
            ->salutation("L'équipe ".config('app.name'));
    }
}
