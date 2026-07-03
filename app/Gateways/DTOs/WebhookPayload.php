<?php

namespace App\Gateways\DTOs;

use App\Enums\PaymentStatus;

/**
 * Payload normalisé issu d'un webhook de passerelle.
 *
 * Chaque Gateway implémente parseWebhook() et retourne cette structure,
 * permettant au WebhookController de traiter tous les gateways de manière
 * identique sans connaître le format propriétaire de chacun.
 */
final readonly class WebhookPayload
{
    public function __construct(
        /** Référence interne PAY-YYYY-NNNNN */
        public string $reference,
        /** Statut normalisé */
        public PaymentStatus $status,
        /** Identifiant de transaction côté passerelle */
        public ?string $gatewayPaymentId = null,
        /** Payload brut pour archivage */
        public array $rawPayload = [],
    ) {}
}
