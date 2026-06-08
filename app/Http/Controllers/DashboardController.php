<?php

namespace App\Http\Controllers;

use App\Models\StockDepot;
use App\Models\Ticket;
use Carbon\CarbonInterface;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();

        return Inertia::render('Dashboard/Index', [
            'stats' => $this->stats($now),
            // 'charts'  => $this->charts($now),
            'recent' => $this->recent(),
            // 'alerts'  => $this->alerts(),
        ]);
    }

    private function stats(CarbonInterface $now): array
    {
        return [
            // 'tickets_open' => Ticket::whereNotIn('status', [...])->count(),

            'tickets_today' => Ticket::whereDate('created_at', $now->toDateString())->count(),

            // 'tickets_done_month' => Ticket::where('status', ...)->count(),

            'low_stock_count' => StockDepot::critique()->count(),

            // 'revenue_month' => Invoice::where('status', 'paid')->sum('total_ttc'),
            // 'avg_repair_days' => ... (nécessite une colonne `closed_at` sur tickets)
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recent(): array
    {
        return [
            'low_stock' => StockDepot::critique()
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
                ]),
        ];
    }
}
