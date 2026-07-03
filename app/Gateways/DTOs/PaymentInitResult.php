<?php

namespace App\Gateways\DTOs;

/**
 * Résultat retourné par PaymentGateway::initiate().
 *
 * Pour une passerelle automatique (PayDunya, Wave…) :
 *   - $redirectUrl est rempli pour rediriger le client vers la page de paiement.
 *   - $gatewayPaymentId contient l'identifiant externe.
 *
 * Pour le mode manuel :
 *   - $redirectUrl est null.
 *   - $instructions contient les coordonnées bancaires / instructions.
 */
final readonly class PaymentInitResult
{
    public function __construct(
        /** Référence interne du paiement (ex: PAY-2026-00001) */
        public string $reference,
        /** URL de redirection pour passerelle externe, null si manuel */
        public ?string $redirectUrl = null,
        /** Identifiant côté passerelle externe */
        public ?string $gatewayPaymentId = null,
        /** Instructions textuelles pour le mode manuel */
        public ?string $instructions = null,
    ) {}
}
