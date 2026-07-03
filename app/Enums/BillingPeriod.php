<?php

namespace App\Enums;

enum BillingPeriod: string
{
    case Monthly = 'monthly';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Mensuel',
            self::Annual => 'Annuel',
        };
    }

    /** Nombre de mois couverts par la période. */
    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Annual => 12,
        };
    }

    /**
     * Montant à facturer : annuel = 10 × mensuel (2 mois offerts).
     */
    public function priceFor(int $monthlyPrice): int
    {
        return match ($this) {
            self::Monthly => $monthlyPrice,
            self::Annual => $monthlyPrice * 10,
        };
    }

    public function discountLabel(): ?string
    {
        return match ($this) {
            self::Monthly => null,
            self::Annual => '2 mois offerts',
        };
    }
}
