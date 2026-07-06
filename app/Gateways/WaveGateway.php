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
 * Passerelle Wave (https://wave.com/fr).
 *
 * Flux :
 *  1. initiate()      → POST /v1/checkout/sessions → redirige vers wave_launch_url
 *  2. verifyWebhook() → vérifie X-Wave-Signature = sha256=HMAC-SHA256(body, webhook_secret)
 *  3. parseWebhook()  → normalise le payload webhook en WebhookPayload
 *
 * Variables d'environnement requises :
 *   WAVE_API_KEY
 *   WAVE_WEBHOOK_SECRET
 */
class WaveGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'wave';
    }

    public function initiate(Shop $shop, Plan $plan, BillingPeriod $period, Payment $payment): PaymentInitResult
    {
        $response = Http::withToken((string) config('payment.wave.api_key'))
            ->post('https://api.wave.com/v1/checkout/sessions', [
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
                'error_url' => route('subscription.index'),
                'success_url' => route('subscription.index'),
                'client_reference' => $payment->reference,
            ]);

        $response->throw();

        $data = $response->json();

        return new PaymentInitResult(
            reference: $payment->reference,
            redirectUrl: $data['wave_launch_url'],
            gatewayPaymentId: $data['id'],
        );
    }

    /**
     * Vérifie la signature HMAC-SHA256 du webhook Wave.
     * Header attendu : X-Wave-Signature: sha256={hex}
     */
    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-Wave-Signature');

        if (! $signature) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), (string) config('payment.wave.webhook_secret'));

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): WebhookPayload
    {
        $data = $request->json()->all();

        $status = match ($data['payment_status'] ?? '') {
            'succeeded' => PaymentStatus::Validated,
            'errored', 'refunded' => PaymentStatus::Rejected,
            default => PaymentStatus::Pending,
        };

        return new WebhookPayload(
            reference: $data['client_reference'] ?? '',
            status: $status,
            gatewayPaymentId: $data['id'] ?? null,
            rawPayload: $data,
        );
    }
}
