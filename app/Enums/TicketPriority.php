<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low    = 'low';
    case Normal = 'normal';
    case High   = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Faible',
            self::Normal => 'Normal',
            self::High   => 'Élevée',
            self::Urgent => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low    => 'default',
            self::Normal => 'info',
            self::High   => 'warning',
            self::Urgent => 'danger',
        };
    }
}
