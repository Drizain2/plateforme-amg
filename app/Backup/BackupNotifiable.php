<?php

namespace App\Backup;

use App\Models\User;
use Illuminate\Notifications\Notifiable;

/**
 * Destinataire des notifications de sauvegarde : tous les super_admins de la plateforme.
 * Si aucun super_admin n'est trouvé, repli sur BACKUP_NOTIFICATION_EMAIL.
 */
class BackupNotifiable
{
    use Notifiable;

    /** @return array<string, string>|string */
    public function routeNotificationForMail(): array|string
    {
        $admins = User::role('super_admin')
            ->whereNotNull('email')
            ->pluck('name', 'email')
            ->toArray();

        if (empty($admins)) {
            return (string) config('backup.notifications.mail.to');
        }

        return $admins;
    }

    public function getKey(): string
    {
        return 'backup-notifiable';
    }
}
