<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $shopName = $notifiable->shop?->name ?? config('app.name');

        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject("Définissez votre mot de passe — {$shopName}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Cliquez sur le bouton ci-dessous pour définir votre mot de passe et accéder à votre espace {$shopName}.")
            ->action('Définir mon mot de passe', $url)
            ->line("Ce lien expire dans {$expireMinutes} minutes.")
            ->line("Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.")
            ->salutation("L'équipe {$shopName}");
    }
}
