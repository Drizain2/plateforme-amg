<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\TicketStatus;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Ticket;
use App\Notifications\InvoiceSent;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function index(): Response
    {
        $filters = request()->only(['search', 'status', 'from', 'to']);

        $invoices = Invoice::with(['customer', 'ticket'])
            ->when(
                $filters['search'] ?? null,
                fn($q, $s) => $q->where('number', 'like', "%$s%")
                    ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%$s%"))
            )
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('issued_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('issued_at', '<=', $v))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => InvoiceResource::collection($invoices),
            'filters' => $filters,
            'statuses' => array_map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ], InvoiceStatus::cases()),
            'summary' => [
                'total_draft' => Invoice::where('status', 'draft')->sum('total_ttc'),
                'total_sent' => Invoice::where('status', 'sent')->sum('total_ttc'),
                'total_paid' => Invoice::where('status', 'paid')
                    ->whereMonth('paid_at', now()->month)
                    ->sum('total_ttc'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Invoices/Create', [
            'customers' => Customer::select('id', 'name', 'email')->get(),
            'tickets' => Ticket::where('status', TicketStatus::Done->value)
                ->doesntHave('invoice')
                ->with('customer:id,name', 'device:id,brand,model')
                ->select('id', 'reference', 'customer_id', 'device_id', 'estimated_price')
                ->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'reference' => $t->reference,
                    'customer' => $t->customer->name,
                    'device' => $t->device->full_name,
                ]),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = DB::transaction(function () use ($request) {
            $invoice = Invoice::create([
                'customer_id' => $request->customer_id,
                'ticket_id' => $request->ticket_id,
                'status' => InvoiceStatus::Draft,
                'tax_rate' => $request->tax_rate,
                'notes' => $request->notes,
                'issued_at' => now(),
                'due_at' => $request->due_at,
            ]);

            foreach ($request->lines as $line) {
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'type' => $line['type'],
                    'label' => $line['label'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);
            }

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice->id)
            ->with('success', "Facture {$invoice->number} créée.");
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('Invoices/Show', [
            'invoice' => (new InvoiceResource(
                $invoice->load(['customer', 'ticket', 'lines'])
            ))->resolve(),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            return back()->with('error', 'Impossible de modifier une facture non brouillon.');
        }

        $invoice->update($request->validated());
        $invoice->load('lines')->recalculate();

        return back()->with('success', 'Facture mise à jour.');
    }

    public function transition(Invoice $invoice, Request $request): RedirectResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
        ]);

        $newStatus = InvoiceStatus::from($request->status);
        $this->invoiceService->transition($invoice, $newStatus);

        // Notifier le client si envoi
        if ($newStatus === InvoiceStatus::Sent) {
            $invoice->customer->notify(new InvoiceSent($invoice->load('shop')));
        }

        return back()->with('success', 'Statut mis à jour.');
    }

    public function storeLine(Invoice $invoice, Request $request): RedirectResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['service', 'part'])],
            'label' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->invoiceService->addLine($invoice, $request->all());

        return back()->with('success', 'Ligne ajoutée.');
    }

    public function destroyLine(Invoice $invoice, InvoiceLine $line): RedirectResponse
    {
        $this->invoiceService->removeLine($line);

        return back()->with('success', 'Ligne supprimée.');
    }

    public function pdf(Invoice $invoice): \Illuminate\Http\Response
    {
        $invoice->load(['customer', 'ticket', 'lines', 'shop']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'shop' => app('current_shop'),
        ]);

        return $pdf->stream("facture-{$invoice->number}.pdf");
    }

    public function publicPdf(int $invoice): \Illuminate\Http\Response
    {
        $invoice = Invoice::withoutGlobalScopes()->with(['customer', 'ticket', 'lines', 'shop'])->findOrFail($invoice);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'shop' => $invoice->shop,
        ]);

        return $pdf->stream("facture-{$invoice->number}.pdf");
    }

    public function fromTicket(Ticket $ticket): RedirectResponse
    {
        $invoice = $this->invoiceService->createFromTicket(
            $ticket->load('parts.part', 'device')
        );

        return redirect()
            ->route('invoices.show', $invoice->id)
            ->with('success', "Facture {$invoice->number} générée depuis le ticket.");
    }
}
