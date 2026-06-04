<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';

    public function label(): string
    {
        return match ($this) {
            self::In => 'In',
            self::Out => 'Out',
            self::TransferIn => 'Transfer In',
            self::TransferOut => 'Transfer Out',
            self::Adjustment => 'Adjustment',
        };
    }

    public function isDebit(): bool
    {
        return in_array($this, [self::Out, self::TransferOut]);
    }
}
