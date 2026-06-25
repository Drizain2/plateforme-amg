<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatus;
use App\Enums\TicketStatus;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\Ticket;
use App\Services\PermissionService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('perm:dashboard.view');
    }

    public function index(): Response
    {
        $now = now();
        $effective = app(PermissionService::class)->effectivePermissions(auth()->user());

        $canTickets = in_array('tickets.view', $effective, true);
        $canStock = in_array('stock.view', $effective, true);
        $canAnalytics = in_array('dashboard.analytics', $effective, true);
        $canPurchases = in_array('purchases.view', $effective, true);

        return Inertia::render('Dashboard/Index', [
            'stats' => $this->stats($now, $canTickets, $canStock, $canAnalytics, $canPurchases),
            'charts' => $this->charts($now, $canTickets, $canStock),
            'recent' => $this->recent($canTickets, $canStock),
            'alerts' => $this->alerts($canTickets),
        ]);
    }

    private function stats(CarbonInterface $now, bool $canTickets, bool $canStock, bool $canAnalytics, bool $canPurchases): array
    {
        $stats = [];

        if ($canTickets) {
            $stats['tickets_open'] = Ticket::whereNotIn('status', [
                TicketStatus::Returned->value,
                TicketStatus::Cancelled->value,
            ])->count();

            $stats['tickets_today'] = Ticket::whereDate('created_at', $now->toDateString())->count();

            $stats['tickets_done_month'] = Ticket::where('status', TicketStatus::Done->value)
                ->whereMonth('closed_at', $now->month)
                ->whereYear('closed_at', $now->year)
                ->count();

            $closedThisMonth = Ticket::whereNotNull('closed_at')
                ->whereMonth('closed_at', $now->month)
                ->whereYear('closed_at', $now->year)
                ->get(['created_at', 'closed_at']);

            $stats['avg_repair_days'] = round(
                $closedThisMonth->avg(fn ($t) => $t->created_at->diffInDays($t->closed_at)) ?? 0,
                1
            );
        }

        if ($canStock) {
            $stats['low_stock_count'] = StockDepot::critique()->count();
        }

        if ($canAnalytics) {
            $stats['revenue_month'] = Invoice::where('status', 'paid')
                ->whereMonth('paid_at', $now->month)
                ->whereYear('paid_at', $now->year)
                ->sum('total_ttc');
        }

        if ($canPurchases) {
            $stats['purchases_pending_count'] = Purchase::whereIn('status', [
                PurchaseStatus::Draft->value,
                PurchaseStatus::Received->value,
            ])->count();
        }

        return $stats;
    }

    private function charts(CarbonInterface $now, bool $canTickets, bool $canStock): array
    {
        if ($canTickets) {
            return [
                'tickets_by_day' => $this->dailyCountSeries(Ticket::class, $now),

                'tickets_by_status' => Ticket::selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->get()
                    ->map(fn ($r) => [
                        'status' => $r->status->label(),
                        'total' => $r->total,
                    ]),

                'tickets_by_depot' => Ticket::selectRaw('depot_id, COUNT(*) as total')
                    ->with('depot:id,name')
                    ->groupBy('depot_id')
                    ->get()
                    ->map(fn ($r) => [
                        'depot' => $r->depot?->name ?? 'Inconnu',
                        'total' => $r->total,
                    ]),
            ];
        }

        if ($canStock) {
            return [
                'stock_movements_by_day' => $this->dailyCountSeries(StockMovement::class, $now),

                'stock_by_depot' => StockDepot::selectRaw('depot_id, SUM(quantity) as total')
                    ->with('depot:id,name')
                    ->groupBy('depot_id')
                    ->get()
                    ->map(fn ($r) => [
                        'depot' => $r->depot?->name ?? 'Inconnu',
                        'total' => (int) $r->total,
                    ]),
            ];
        }

        return [];
    }

    /**
     * Série de 30 points journaliers zero-filled (comptage par jour) pour un
     * modèle disposant d'une colonne created_at.
     *
     * @return array<int, array{date: string, label: string, total: int}>
     */
    private function dailyCountSeries(string $modelClass, CarbonInterface $now, int $days = 30): array
    {
        $counts = $modelClass::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $now->copy()->subDays($days - 1)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $series = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->toDateString();
            $series[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d/m'),
                'total' => $counts->get($date)?->total ?? 0,
            ];
        }

        return $series;
    }

    private function recent(bool $canTickets, bool $canStock): array
    {
        $recent = [];

        if ($canTickets) {
            $recent['tickets'] = Ticket::with(['customer', 'device', 'technicien'])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'reference' => $t->reference,
                    'status_label' => $t->status->label(),
                    'status_color' => $t->status->color(),
                    'priority_color' => $t->priority->color(),
                    'customer_name' => $t->customer->name,
                    'device_name' => $t->device->full_name,
                    'technicien' => $t->technicien?->name,
                    'created_at' => $t->created_at->diffForHumans(),
                ]);
        }

        if ($canStock) {
            $recent['low_stock'] = StockDepot::critique()
                ->with(['part:id,name', 'depot:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (StockDepot $stock) => [
                    'id' => $stock->id,
                    'part_name' => $stock->part->name,
                    'depot_name' => $stock->depot->name,
                    'quantity' => $stock->quantity,
                    'alert_quantity' => $stock->alert_quantity,
                ]);
        }

        return $recent;
    }

    private function alerts(bool $canTickets): array
    {
        if (! $canTickets) {
            return [];
        }

        return [
            'overdue' => Ticket::whereNotNull('estimated_return_date')
                ->whereDate('estimated_return_date', '<', now()->toDateString())
                ->whereNotIn('status', [
                    TicketStatus::Returned->value,
                    TicketStatus::Cancelled->value,
                    TicketStatus::Done->value,
                ])
                ->with('customer:id,name')
                ->limit(5)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'reference' => $t->reference,
                    'customer_name' => $t->customer->name,
                    'overdue_days' => (int) now()->diffInDays($t->estimated_return_date),
                ]),
        ];
    }
}
