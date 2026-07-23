<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending'; // ← AJOUTEZ CETTE LIGNE
    case Trial = 'trial';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Trial => 'Essai',
            self::Active => 'Actif',
            self::Expired => 'Expiré',
            self::Cancelled => 'Annulé',
            self::Suspended => 'Suspendu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Trial => 'info',
            self::Active => 'success',
            self::Expired => 'danger',
            self::Cancelled => 'default',
            self::Suspended => 'warning',
        };
    }

    public function isAccessible(): bool
    {
        return in_array($this, [self::Trial, self::Active], true);
    }
}
