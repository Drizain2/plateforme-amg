<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Received     = 'received';
    case Diagnosing   = 'diagnosing';
    case WaitingParts = 'waiting_parts';
    case Repairing    = 'repairing';
    case Done         = 'done';
    case Returned     = 'returned';
    case Cancelled    = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Received     => 'Reçu',
            self::Diagnosing   => 'Diagnostic',
            self::WaitingParts => 'Attente pièces',
            self::Repairing    => 'En réparation',
            self::Done         => 'Terminé',
            self::Returned     => 'Rendu',
            self::Cancelled    => 'Annulé',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Received     => 'info',
            self::Diagnosing   => 'warning',
            self::WaitingParts => 'warning',
            self::Repairing    => 'info',
            self::Done         => 'success',
            self::Returned     => 'default',
            self::Cancelled    => 'danger',
        };
    }

    public function transitions(): array
    {
        return match($this) {
            self::Received     => [self::Diagnosing, self::Cancelled],
            self::Diagnosing   => [self::WaitingParts, self::Repairing, self::Cancelled],
            self::WaitingParts => [self::Repairing, self::Cancelled],
            self::Repairing    => [self::Done],
            self::Done         => [self::Returned],
            default            => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->transitions());
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Returned, self::Cancelled]);
    }
}
