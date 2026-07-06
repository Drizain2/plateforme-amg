<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingPeriod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'kpis' => $this->kpis(),
            'acquisition' => $this->acquisitionByWeek(),
            'pendingPayments' => Payment::with(['shop', 'plan'])
                ->where('status', PaymentStatus::Pending)
                ->latest()
                ->limit(8)
                ->get(),
            'recentShops' => Shop::with(['plan', 'admin'])
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    /** @return array<string, mixed> */
    private function kpis(): array
    {
        // ── MRR (somme mensuelle des abonnements actifs) ──────────────────
        $activeSubscriptions = Subscription::with('plan')
            ->where('status', SubscriptionStatus::Active->value)
            ->where('ends_at', '>', now())
            ->get();

        $mrr = (int) $activeSubscriptions->sum(function (Subscription $sub): float {
            if (! $sub->plan) {
                return 0;
            }

            return $sub->billing_period === BillingPeriod::Annual
                ? $sub->plan->price * 10 / 12
                : $sub->plan->price;
        });

        // ── Comptages ateliers ────────────────────────────────────────────
        $suspended = Shop::where('is_active', false)->count();

        $active = Shop::where('is_active', true)
            ->whereHas('subscriptions', fn ($q) => $q
                ->where('status', SubscriptionStatus::Active->value)
                ->where('ends_at', '>', now()))
            ->count();

        $trial = Shop::where('is_active', true)
            ->where('trial_ends_at', '>', now())
            ->whereDoesntHave('subscriptions', fn ($q) => $q
                ->where('status', SubscriptionStatus::Active->value)
                ->where('ends_at', '>', now()))
            ->count();

        $churned = Shop::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<', now()))
            ->whereDoesntHave('subscriptions', fn ($q) => $q
                ->where('status', SubscriptionStatus::Active->value)
                ->where('ends_at', '>', now()))
            ->count();

        $total = $active + $trial + $churned + $suspended;

        // ── Taux de conversion essai → payant ─────────────────────────────
        $converted = Shop::whereHas('payments', fn ($q) => $q->where('status', PaymentStatus::Validated->value))->count();
        $conversionRate = $total > 0 ? round($converted / $total * 100, 1) : 0;

        // ── Churn ce mois (abonnements expirés ce mois sans renouvellement)
        $churnedThisMonth = Subscription::where('status', SubscriptionStatus::Active->value)
            ->whereMonth('ends_at', now()->month)
            ->whereYear('ends_at', now()->year)
            ->where('ends_at', '<', now())
            ->whereHas('shop', fn ($q) => $q->where('is_active', true))
            ->whereDoesntHave('shop.subscriptions', fn ($q) => $q
                ->where('status', SubscriptionStatus::Active->value)
                ->where('ends_at', '>', now()))
            ->count();

        $pendingCount = Payment::where('status', PaymentStatus::Pending)->count();

        return [
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'shops' => compact('total', 'active', 'trial', 'churned', 'suspended'),
            'conversionRate' => $conversionRate,
            'churnedThisMonth' => $churnedThisMonth,
            'pendingPaymentsCount' => $pendingCount,
        ];
    }

    /**
     * Nouveaux ateliers par semaine sur les 12 dernières semaines.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function acquisitionByWeek(): array
    {
        $since = now()->subWeeks(11)->startOfWeek(Carbon::MONDAY);

        $grouped = Shop::where('created_at', '>=', $since)
            ->get(['created_at'])
            ->groupBy(fn (Shop $s) => $s->created_at->startOfWeek(Carbon::MONDAY)->format('Y-W'));

        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $weeks[] = [
                'label' => $start->format('d/m'),
                'count' => $grouped->get($start->format('Y-W'), collect())->count(),
            ];
        }

        return $weeks;
    }
}
