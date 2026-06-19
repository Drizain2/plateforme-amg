<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Draft = 'draft';
    case Received = 'received';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Received => 'Reçue',
            self::Paid => 'Payée',
            self::Cancelled => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'default',
            self::Received => 'info',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Received, self::Cancelled]),
            self::Received => in_array($next, [self::Paid]),
            default => false,
        };
    }
}
