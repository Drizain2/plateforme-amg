<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\BillingPeriod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Gateways\DTOs\PaymentInitResult;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentRejected;
use App\Notifications\PaymentValidated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SubscriptionService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * Initie une demande d'abonnement pour un atelier.
     *
     * Pour un plan gratuit → active immédiatement sans paiement.
     * Pour un plan payant → crée un paiement `pending` et notifie les super-admins.
     */
    public function requestSubscription(Shop $shop, Plan $plan, BillingPeriod $period, User $by): PaymentInitResult
    {
        $amount = $period->priceFor($plan->price);

        // Plan gratuit : activation immédiate, pas de paiement
        if ($amount === 0) {
            $this->activateFree($shop, $plan, $period);

            return new PaymentInitResult(reference: 'GRATUIT');
        }

        return DB::transaction(function () use ($shop, $plan, $period, $amount) {
            $payment = Payment::create([
                'shop_id' => $shop->id,
                'plan_id' => $plan->id,
                'billing_period' => $period,
                'amount' => $amount,
                'currency' => 'XOF',
                'reference' => $this->generateReference(),
                'status' => PaymentStatus::Pending,
                'gateway' => $this->gateway->name(),
            ]);

            $result = $this->gateway->initiate($shop, $plan, $period, $payment);

            // Notifie tous les super-admins de la plateforme
            $superAdmins = User::role('super_admin')->get();
            Notification::send($superAdmins, new PaymentReceived($payment));

            return $result;
        });
    }

    /**
     * Valide un paiement en attente et active (ou prolonge) l'abonnement.
     * Appelé par l'admin de la plateforme.
     */
    public function validatePayment(Payment $payment, User $by): Subscription
    {
        abort_if($payment->status !== PaymentStatus::Pending, 422, 'Ce paiement ne peut pas être validé.');

        return DB::transaction(function () use ($payment, $by) {
            $payment->update([
                'status' => PaymentStatus::Validated,
                'validated_by' => $by->id,
                'validated_at' => now(),
            ]);

            $subscription = $this->createOrExtendSubscription($payment);

            $payment->update(['subscription_id' => $subscription->id]);

            // Notifie l'admin de l'atelier
            $shopAdmin = $payment->shop->admin;
            if ($shopAdmin) {
                $shopAdmin->notify(new PaymentValidated($payment, $subscription));
            }

            return $subscription;
        });
    }

    /**
     * Rejette un paiement en attente.
     */
    public function rejectPayment(Payment $payment, User $by, string $reason): void
    {
        abort_if($payment->status !== PaymentStatus::Pending, 422, 'Ce paiement ne peut pas être rejeté.');

        $payment->update([
            'status' => PaymentStatus::Rejected,
            'rejected_reason' => $reason,
            'rejected_at' => now(),
            'validated_by' => $by->id,
        ]);

        $shopAdmin = $payment->shop->admin;
        if ($shopAdmin) {
            $shopAdmin->notify(new PaymentRejected($payment));
        }
    }

    /**
     * Traite un webhook normalisé provenant d'une passerelle externe.
     * Point d'entrée unique, indépendant de la passerelle.
     */
    public function handleWebhook(string $reference, PaymentStatus $status, ?string $gatewayPaymentId, array $rawPayload): void
    {
        $payment = Payment::where('reference', $reference)->firstOrFail();

        if ($payment->status !== PaymentStatus::Pending) {
            return; // idempotent
        }

        $payment->update([
            'gateway_payment_id' => $gatewayPaymentId,
            'gateway_response' => $rawPayload,
        ]);

        $platformBot = User::role('super_admin')->first();

        match ($status) {
            PaymentStatus::Validated => $this->validatePayment($payment, $platformBot),
            PaymentStatus::Rejected => $this->rejectPayment($payment, $platformBot, 'Rejet automatique passerelle'),
            default => null,
        };
    }

    // ── Privé ─────────────────────────────────────────────────────────────────

    private function activateFree(Shop $shop, Plan $plan, BillingPeriod $period): Subscription
    {
        $this->cancelCurrentSubscription($shop);

        return Subscription::create([
            'shop_id' => $shop->id,
            'plan_id' => $plan->id,
            'billing_period' => $period,
            'starts_at' => now(),
            'ends_at' => now()->addYears(10), // plan gratuit = perpétuel
            'status' => SubscriptionStatus::Active,
            'gateway' => $this->gateway->name(),
        ]);
    }

    private function createOrExtendSubscription(Payment $payment): Subscription
    {
        $shop = $payment->shop;
        $period = $payment->billing_period;

        // Si un abonnement actif existe, on prolonge à partir de sa fin
        $existing = $shop->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trial->value])
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();

        $startsAt = $existing ? $existing->ends_at : now();
        $endsAt = $startsAt->copy()->addMonths($period->months());

        $this->cancelCurrentSubscription($shop);

        return Subscription::create([
            'shop_id' => $shop->id,
            'plan_id' => $payment->plan_id,
            'billing_period' => $period,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => SubscriptionStatus::Active,
            'gateway' => $payment->gateway,
        ]);
    }

    private function cancelCurrentSubscription(Shop $shop): void
    {
        $shop->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trial->value])
            ->update(['status' => SubscriptionStatus::Cancelled->value, 'cancelled_at' => now()]);
    }

    private function generateReference(): string
    {
        $year = now()->year;
        $count = Payment::whereYear('created_at', $year)->count() + 1;

        return 'PAY-'.$year.'-'.str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
