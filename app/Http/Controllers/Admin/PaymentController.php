<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function index(Request $request): Response
    {
        $payments = Payment::with(['shop', 'plan', 'validatedBy'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('shop', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only('status', 'search'),
        ]);
    }

    public function approve(Payment $payment): RedirectResponse
    {
        $this->subscriptions->validatePayment($payment, auth()->user());

        return back()->with('success', 'Paiement validé et abonnement activé.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->subscriptions->rejectPayment($payment, auth()->user(), $request->reason);

        return back()->with('success', 'Paiement rejeté.');
    }
}
