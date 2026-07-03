<?php

namespace App\Gateways;

use App\Contracts\PaymentGateway;
use App\Enums\BillingPeriod;
use App\Enums\PaymentStatus;
use App\Gateways\DTOs\PaymentInitResult;
use App\Gateways\DTOs\WebhookPayload;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Shop;
use Illuminate\Http\Request;

/**
 * Passerelle de paiement manuel.
 *
 * Aucune API externe : l'admin de la plateforme valide les paiements
 * manuellement (virement bancaire, cash, Mobile Money hors-ligne…).
 * Les webhooks ne sont pas utilisés dans ce mode.
 */
class ManualGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function initiate(Shop $shop, Plan $plan, BillingPeriod $period, Payment $payment): PaymentInitResult
    {
        // Pas de redirection : on retourne uniquement la référence et
        // les instructions de paiement à afficher à l'utilisateur.
        return new PaymentInitResult(
            reference: $payment->reference,
            instructions: $this->buildInstructions($payment),
        );
    }

    /**
     * Le mode manuel n'envoie aucun webhook : cette méthode retourne
     * toujours false pour que le WebhookController ignore les requêtes
     * non-authentifiées adressées au nom 'manual'.
     */
    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(Request $request): WebhookPayload
    {
        // Non utilisé en mode manuel — implémenté pour satisfaire le contrat.
        return new WebhookPayload(
            reference: '',
            status: PaymentStatus::Pending,
        );
    }

    private function buildInstructions(Payment $payment): string
    {
        $amount = number_format($payment->amount, 0, ',', ' ').' '.$payment->currency;

        return "Veuillez effectuer un virement de {$amount} en indiquant la référence {$payment->reference}. "
            .'Votre abonnement sera activé dès réception et validation du paiement par notre équipe.';
    }
}
