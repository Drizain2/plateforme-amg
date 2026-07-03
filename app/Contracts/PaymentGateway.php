<?php

namespace App\Contracts;

use App\Enums\BillingPeriod;
use App\Gateways\DTOs\PaymentInitResult;
use App\Gateways\DTOs\WebhookPayload;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Shop;
use Illuminate\Http\Request;

/**
 * Contrat d'intégration de passerelle de paiement.
 *
 * Pour ajouter un nouveau prestataire (PayDunya, Wave, Stripe…) :
 *   1. Créer app/Gateways/YourGateway.php qui implémente cette interface.
 *   2. Lier l'alias dans AppServiceProvider : $app->bind('payment.gateway.yourgateway', YourGateway::class).
 *   3. Ajouter la route webhook si nécessaire (POST /webhooks/payment/yourgateway).
 *   4. Aucun autre fichier n'a besoin d'être modifié.
 */
interface PaymentGateway
{
    /** Identifiant court utilisé dans la route webhook et la colonne `gateway`. */
    public function name(): string;

    /**
     * Initie un paiement pour un plan donné.
     * Retourne un PaymentInitResult avec l'URL de redirection ou les instructions manuelles.
     */
    public function initiate(Shop $shop, Plan $plan, BillingPeriod $period, Payment $payment): PaymentInitResult;

    /**
     * Vérifie l'authenticité d'un webhook entrant (signature HMAC, token, etc.).
     * Doit retourner false si la requête ne provient pas de la passerelle attendue.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Convertit le payload brut du webhook en PaymentWebhookPayload normalisé.
     * Appelé uniquement si verifyWebhook() retourne true.
     */
    public function parseWebhook(Request $request): WebhookPayload;
}
