<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Rejected = 'rejected';
    case Refunded = 'refunded'; // préparé pour future passerelle

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Validated => 'Validé',
            self::Rejected => 'Rejeté',
            self::Refunded => 'Remboursé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Validated => 'success',
            self::Rejected => 'danger',
            self::Refunded => 'info',
        };
    }
}
