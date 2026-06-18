<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\TicketStatus;
use App\Models\Invoice;
use App\Models\Ticket;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('perm:dashboard.analytics');
    }

    public function cash(Request $request): Response
    {
        $from = Carbon::parse($request->get('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->get('to', now()->endOfMonth()->toDateString()))->endOfDay();

        return Inertia::render('Reports/Cash', [
            'summary' => $this->summary($from, $to),
            'by_period' => $this->byPeriod($from, $to),
            'by_technician' => $this->byTechnician($from, $to),
            'top_clients' => $this->topClients($from, $to),
            'uninvoiced_tickets' => $this->uninvoicedTickets($from, $to),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    private function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $avgDays = Invoice::where('status', InvoiceStatus::Paid)
            ->whereBetween('paid_at', [$from, $to])
            ->whereNotNull('issued_at')
            ->selectRaw('AVG(DATEDIFF(paid_at, issued_at)) as avg')
            ->value('avg');

        return [
            'revenue_paid' => (float) Invoice::where('status', InvoiceStatus::Paid)
                ->whereBetween('paid_at', [$from, $to])
                ->sum('total_ttc'),
            'revenue_pending' => (float) Invoice::where('status', InvoiceStatus::Sent)->sum('total_ttc'),
            'invoices_count' => Invoice::whereBetween('issued_at', [$from, $to])->count(),
            'invoices_paid_count' => Invoice::where('status', InvoiceStatus::Paid)
                ->whereBetween('paid_at', [$from, $to])
                ->count(),
            'avg_payment_days' => round($avgDays ?? 0, 1),
            'uninvoiced_count' => Ticket::whereIn('status', [
                TicketStatus::Done->value,
                TicketStatus::Returned->value,
            ])
                ->whereBetween('closed_at', [$from, $to])
                ->doesntHave('invoice')
                ->count(),
        ];
    }

    private function byPeriod(CarbonInterface $from, CarbonInterface $to): array
    {
        $groupByMonth = $from->diffInDays($to) > 90;

        if ($groupByMonth) {
            $rows = Invoice::where('status', InvoiceStatus::Paid)
                ->whereBetween('paid_at', [$from, $to])
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as period, SUM(total_ttc) as total, COUNT(*) as count")
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->keyBy('period');

            $points = [];
            $cursor = $from->copy()->startOfMonth();
            while ($cursor->lte($to)) {
                $key = $cursor->format('Y-m');
                $points[] = [
                    'label' => $cursor->translatedFormat('M Y'),
                    'total' => (float) ($rows->get($key)?->total ?? 0),
                    'count' => (int) ($rows->get($key)?->count ?? 0),
                ];
                $cursor->addMonth();
            }
        } else {
            $rows = Invoice::where('status', InvoiceStatus::Paid)
                ->whereBetween('paid_at', [$from, $to])
                ->selectRaw('DATE(paid_at) as period, SUM(total_ttc) as total, COUNT(*) as count')
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->keyBy('period');

            $points = [];
            $cursor = $from->copy()->startOfDay();
            while ($cursor->lte($to)) {
                $key = $cursor->toDateString();
                $points[] = [
                    'label' => $cursor->format('d/m'),
                    'total' => (float) ($rows->get($key)?->total ?? 0),
                    'count' => (int) ($rows->get($key)?->count ?? 0),
                ];
                $cursor->addDay();
            }
        }

        return $points;
    }

    private function byTechnician(CarbonInterface $from, CarbonInterface $to): array
    {
        return Invoice::where('invoices.status', InvoiceStatus::Paid)
            ->whereBetween('invoices.paid_at', [$from, $to])
            ->join('tickets', 'tickets.id', '=', 'invoices.ticket_id')
            ->join('users', 'users.id', '=', 'tickets.technicien_id')
            ->selectRaw('users.name as technician, SUM(invoices.total_ttc) as total, COUNT(invoices.id) as count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'technician' => $r->technician,
                'total' => (float) $r->total,
                'count' => (int) $r->count,
            ])
            ->toArray();
    }

    private function topClients(CarbonInterface $from, CarbonInterface $to): array
    {
        return Invoice::where('invoices.status', InvoiceStatus::Paid)
            ->whereBetween('invoices.paid_at', [$from, $to])
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->selectRaw('customers.name as customer, SUM(invoices.total_ttc) as total, COUNT(invoices.id) as count')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'customer' => $r->customer,
                'total' => (float) $r->total,
                'count' => (int) $r->count,
            ])
            ->toArray();
    }

    private function uninvoicedTickets(CarbonInterface $from, CarbonInterface $to): array
    {
        return Ticket::with('customer:id,name')
            ->whereIn('status', [TicketStatus::Done->value, TicketStatus::Returned->value])
            ->whereBetween('closed_at', [$from, $to])
            ->doesntHave('invoice')
            ->latest('closed_at')
            ->limit(20)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'customer' => $t->customer?->name ?? '—',
                'status' => $t->status->label(),
                'closed_at' => $t->closed_at?->format('d/m/Y'),
            ])
            ->toArray();
    }
}
