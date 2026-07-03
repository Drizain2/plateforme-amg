<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /**
     * Point d'entrée unique pour les webhooks de toutes les passerelles.
     *
     * Le gateway est résolu par son nom ({gateway} dans l'URL) afin que
     * chaque passerelle externe ait une URL dédiée tout en partageant le
     * même contrôleur. Pour ajouter une passerelle : enregistrer le binding
     * 'payment.gateway.<name>' dans AppServiceProvider.
     */
    public function handle(Request $request, string $gateway): Response
    {
        $bindingKey = 'payment.gateway.'.$gateway;

        if (! app()->bound($bindingKey)) {
            abort(404, "Gateway '{$gateway}' not found.");
        }

        /** @var PaymentGateway $gatewayInstance */
        $gatewayInstance = app($bindingKey);

        if (! $gatewayInstance->verifyWebhook($request)) {
            abort(401, 'Webhook signature invalid.');
        }

        $payload = $gatewayInstance->parseWebhook($request);

        $this->subscriptions->handleWebhook(
            reference: $payload->reference,
            status: $payload->status,
            gatewayPaymentId: $payload->gatewayPaymentId,
            rawPayload: $payload->rawPayload,
        );

        return response('OK', 200);
    }
}
