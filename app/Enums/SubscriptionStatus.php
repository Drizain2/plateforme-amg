<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
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
