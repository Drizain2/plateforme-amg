<?php

namespace App\Http\Controllers;

use App\Enums\BillingPeriod;
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

        return Inertia::render('Subscription/Index', [
            'shop' => $shop,
            'subscription' => $shop->activeSubscription,
            'payments' => $payments,
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
            'success' => 'Demande enregistrée. Référence : '.$result->reference,
            'instructions' => $result->instructions,
            'reference' => $result->reference,
        ]);
    }
}
