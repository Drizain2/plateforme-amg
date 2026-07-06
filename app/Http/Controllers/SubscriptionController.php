<?php

namespace App\Http\Controllers;

use App\Enums\BillingPeriod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions)
    {
        $this->middleware('perm:settings.manage');
    }

    /**
     * Affiche l'historique des paiements + abonnement actif de l'atelier.
     */
    public function index(): Response
    {
        $shop = app('current_shop')->load(['plan', 'activeSubscription.plan']);

        $payments = Payment::where('shop_id', $shop->id)
            ->with('plan')
            ->latest()
            ->paginate(15);

        $plans = Plan::where('is_active', true)
            ->where('price', '>', 0)
            ->orderBy('price')
            ->get(['id', 'name', 'slug', 'price', 'description', 'max_users', 'max_depots', 'features']);

        $hasPendingPayment = Payment::where('shop_id', $shop->id)
            ->where('status', PaymentStatus::Pending)
            ->exists();

        return Inertia::render('Subscription/Index', [
            'shop' => $shop,
            'subscription' => $shop->activeSubscription,
            'payments' => $payments,
            'plans' => $plans,
            'hasPendingPayment' => $hasPendingPayment,
        ]);
    }

    /**
     * Initie une demande d'abonnement (crée un paiement pending).
     */
    public function subscribe(Request $request): RedirectResponse|BaseResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_period' => ['required', 'in:monthly,annual'],
        ]);

        $shop = app('current_shop');
        $plan = Plan::findOrFail($request->plan_id);
        $period = BillingPeriod::from($request->billing_period);

        // Annule les paiements pending existants pour éviter les doublons.
        Payment::where('shop_id', $shop->id)
            ->where('status', PaymentStatus::Pending)
            ->update(['status' => PaymentStatus::Rejected, 'rejected_reason' => 'Remplacé par une nouvelle demande']);

        $result = $this->subscriptions->requestSubscription($shop, $plan, $period, auth()->user());

        if ($result->reference === 'GRATUIT') {
            return back()->with('success', 'Abonnement gratuit activé.');
        }

        // Passerelle automatique (PayDunya, Wave…) : redirige vers la page de paiement.
        if ($result->redirectUrl) {
            return Inertia::location($result->redirectUrl);
        }

        // Mode manuel : affiche les instructions de virement.
        return back()->with([
            'success' => 'Demande enregistrée.',
            'instructions' => $result->instructions,
            'reference' => $result->reference,
        ]);
    }
}
