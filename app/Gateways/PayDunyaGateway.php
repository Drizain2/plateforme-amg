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
use Illuminate\Support\Facades\Http;

/**
 * Passerelle PayDunya (https://paydunya.com).
 *
 * Flux :
 *  1. initiate()     → POST /checkout-invoice/create → redirige l'utilisateur
 *  2. verifyWebhook() → vérifie HMAC-SHA256(token, master_key) == data.hash
 *  3. parseWebhook() → normalise le payload IPN en WebhookPayload
 *
 * Variables d'environnement requises :
 *   PAYDUNYA_MODE        sandbox | live
 *   PAYDUNYA_MASTER_KEY
 *   PAYDUNYA_PRIVATE_KEY
 *   PAYDUNYA_PUBLIC_KEY
 *   PAYDUNYA_TOKEN
 */
class PayDunyaGateway implements PaymentGateway
{
    private string $apiBase;

    private string $checkoutBase;

    private string $masterKey;

    public function __construct()
    {
        $isSandbox = config('payment.paydunya.mode') !== 'live';

        $this->apiBase = $isSandbox
            ? 'https://app.paydunya.com/sandbox-api/v1'
            : 'https://app.paydunya.com/api/v1';

        $this->checkoutBase = $isSandbox
            ? 'https://app.paydunya.com/sandbox-checkout/receipt'
            : 'https://app.paydunya.com/checkout/receipt';

        $this->masterKey = (string) config('payment.paydunya.master_key');
    }

    public function name(): string
    {
        return 'paydunya';
    }

    public function initiate(Shop $shop, Plan $plan, BillingPeriod $period, Payment $payment): PaymentInitResult
    {
        $response = Http::withHeaders([
            'PAYDUNYA-MASTER-KEY' => $this->masterKey,
            'PAYDUNYA-PRIVATE-KEY' => config('payment.paydunya.private_key'),
            'PAYDUNYA-PUBLIC-KEY' => config('payment.paydunya.public_key'),
            'PAYDUNYA-TOKEN' => config('payment.paydunya.token'),
        ])->post("{$this->apiBase}/checkout-invoice/create", [
            'invoice' => [
                'total_amount' => $payment->amount,
                'description' => "Abonnement {$plan->name} — {$period->label()} ({$shop->name})",
            ],
            'store' => [
                'name' => config('app.name'),
                'tagline' => 'Logiciel de gestion pour ateliers de réparation',
            ],
            'custom_data' => [
                'payment_reference' => $payment->reference,
            ],
            'actions' => [
                'cancel_url' => route('subscription.index'),
                'return_url' => route('subscription.index'),
                'callback_url' => route('webhooks.handle', ['gateway' => 'paydunya']),
            ],
        ]);

        $response->throw();

        $token = $response->json('token');

        return new PaymentInitResult(
            reference: $payment->reference,
            redirectUrl: "{$this->checkoutBase}/{$token}",
            gatewayPaymentId: $token,
        );
    }

    /**
     * Vérifie l'authenticité du webhook IPN PayDunya.
     * Le champ data.hash = HMAC-SHA256(invoice.token, master_key).
     */
    public function verifyWebhook(Request $request): bool
    {
        $data = $request->json('data', []);
        $token = $data['invoice']['token'] ?? null;
        $receivedHash = $data['hash'] ?? null;

        if (! $token || ! $receivedHash) {
            return false;
        }

        $expectedHash = hash_hmac('sha256', $token, $this->masterKey);

        return hash_equals($expectedHash, $receivedHash);
    }

    public function parseWebhook(Request $request): WebhookPayload
    {
        $data = $request->json('data', []);
        $invoice = $data['invoice'] ?? [];

        $status = match ($data['status'] ?? '') {
            'completed' => PaymentStatus::Validated,
            'cancelled', 'failed' => PaymentStatus::Rejected,
            default => PaymentStatus::Pending,
        };

        return new WebhookPayload(
            reference: $invoice['custom_data']['payment_reference'] ?? '',
            status: $status,
            gatewayPaymentId: $invoice['token'] ?? null,
            rawPayload: $data,
        );
    }
}
