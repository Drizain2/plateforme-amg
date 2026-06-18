<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Invoice;
use App\Models\StockDepot;
use App\Models\Ticket;
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

        return Inertia::render('Dashboard/Index', [
            'stats' => $this->stats($now),
            'charts' => $this->charts($now),
            'recent' => $this->recent(),
            'alerts' => $this->alerts(),
        ]);
    }

    private function stats(CarbonInterface $now): array
    {
        return [
            'tickets_open' => Ticket::whereNotIn('status', [
                TicketStatus::Returned->value,
                TicketStatus::Cancelled->value,
            ])->count(),

            'tickets_today' => Ticket::whereDate('created_at', $now->toDateString())->count(),

            'tickets_done_month' => Ticket::where('status', TicketStatus::Done->value)
                ->whereMonth('closed_at', $now->month)
                ->whereYear('closed_at', $now->year)
                ->count(),

            'low_stock_count' => StockDepot::critique()->count(),

            'revenue_month' => Invoice::where('status', 'paid')
                ->whereMonth('paid_at', $now->month)
                ->whereYear('paid_at', $now->year)
                ->sum('total_ttc'),

            'avg_repair_days' => round(
                Ticket::whereNotNull('closed_at')
                    ->whereMonth('closed_at', $now->month)
                    ->selectRaw('AVG(DATEDIFF(closed_at, created_at)) as avg')
                    ->value('avg') ?? 0,
                1
            ),
        ];
    }

    private function charts(CarbonInterface $now): array
    {
        // Tickets par jour sur les 30 derniers jours
        $ticketsByDay = Ticket::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->toDateString();
            $days->push([
                'date' => $date,
                'label' => Carbon::parse($date)->format('d/m'),
                'total' => $ticketsByDay->get($date)?->total ?? 0,
            ]);
        }

        // Tickets par statut
        $byStatus = Ticket::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->map(fn($r) => [
                'status' => $r->status->label(),
                'total' => $r->total,
            ]);

        // Tickets par dépôt
        $byDepot = Ticket::selectRaw('depot_id, COUNT(*) as total')
            ->with('depot:id,name')
            ->groupBy('depot_id')
            ->get()
            ->map(fn($r) => [
                'depot' => $r->depot?->name ?? 'Inconnu',
                'total' => $r->total,
            ]);

        return [
            'tickets_by_day' => $days,
            'tickets_by_status' => $byStatus,
            'tickets_by_depot' => $byDepot,
        ];
    }

    private function recent(): array
    {
        return [
            'tickets' => Ticket::with(['customer', 'device', 'technicien'])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'reference' => $t->reference,
                    'status_label' => $t->status->label(),
                    'status_color' => $t->status->color(),
                    'priority_color' => $t->priority->color(),
                    'customer_name' => $t->customer->name,
                    'device_name' => $t->device->full_name,
                    'technicien' => $t->technicien?->name,
                    'created_at' => $t->created_at->diffForHumans(),
                ]),

            'low_stock' => StockDepot::critique()
                ->with(['part:id,name', 'depot:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn(StockDepot $stock) => [
                    'id' => $stock->id,
                    'part_name' => $stock->part->name,
                    'depot_name' => $stock->depot->name,
                    'quantity' => $stock->quantity,
                    'alert_quantity' => $stock->alert_quantity,
                ]),
        ];
    }

    private function alerts(): array
    {
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
                ->map(fn($t) => [
                    'id' => $t->id,
                    'reference' => $t->reference,
                    'customer_name' => $t->customer->name,
                    'overdue_days' => (int) now()->diffInDays($t->estimated_return_date),
                ]),
        ];
    }
}
