<?php

namespace App\Enums;

enum StockCountStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Validated => 'Validé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'default',
            self::Validated => 'success',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return $this === self::Draft && $next === self::Validated;
    }
}
