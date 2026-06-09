<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransitionTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Depot;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enums\TicketStatus;
use App\Enums\TicketPriority;
use App\Http\Requests\AddNoteRequest;
use App\Http\Requests\ConsumePartRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Customer;
use App\Models\Device;
use App\Models\StockDepot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function index()
    {
        $filters = request()->only(['search', 'status', 'priority', 'depot_id', 'technician_id']);

        $tickets = Ticket::with(['customer', 'device', 'technician', 'depot'])
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets'     => TicketResource::collection($tickets),
            'filters'     => $filters,
            'depots'      => Depot::select('id', 'name')->get(),
            'technicians' => User::role('technician')->select('id', 'name')->get(),
            'statuses'    => array_map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ], TicketStatus::cases()),
            'priorities'  => array_map(fn($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ], TicketPriority::cases()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tickets/Create', [
            'depots'      => Depot::select('id', 'name')->where('is_active', true)->get(),
            'technicians' => User::role('technician')->select('id', 'name')->get(),
            'priorities'  => array_map(fn($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ], TicketPriority::cases()),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            // Client — existant ou nouveau
            $customer = $request->customer_id
                ? Customer::findOrFail($request->customer_id)
                : Customer::create([
                    'name'  => $request->customer_name,
                    'email' => $request->customer_email,
                    'phone' => $request->customer_phone,
                ]);

            // Appareil — existant ou nouveau
            $device = $request->device_id
                ? Device::findOrFail($request->device_id)
                : Device::create([
                    'customer_id'   => $customer->id,
                    'type'          => $request->device_type,
                    'brand'         => $request->device_brand,
                    'model'         => $request->device_model,
                    'serial_number' => $request->device_serial,
                    'color'         => $request->device_color,
                    'condition_in'  => $request->condition_in,
                ]);

            $this->ticketService->create([
                'depot_id'              => $request->depot_id,
                'customer_id'           => $customer->id,
                'device_id'             => $device->id,
                'technician_id'         => $request->technician_id,
                'priority'              => $request->priority,
                'description'           => $request->description,
                'estimated_return_date' => $request->estimated_return_date,
            ], $request->user());
        });

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket créé.');
    }

    public function show(Ticket $ticket): Response
    {
        $ticket->load([
            'customer',
            'device',
            'technician',
            'depot',
            'events.user',
            'parts.part',
        ]);

        return Inertia::render('Tickets/Show', [
            'ticket'      => new TicketResource($ticket),
            'technicians' => User::role('technician')->select('id', 'name')->get(),
            'depotParts'  => StockDepot::where('depot_id', $ticket->depot_id)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->select('id', 'name', 'quantity', 'unit_price')
                ->get(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->update($request->validated());
        return back()->with('success', 'Ticket mis à jour.');
    }

    public function transition(TransitionTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->ticketService->transition(
            $ticket,
            TicketStatus::from($request->status),
            $request->user(),
            $request->note,
        );

        return back()->with('success', 'Statut mis à jour.');
    }

    public function addNote(AddNoteRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->ticketService->addNote($ticket, $request->note, $request->user());
        return back()->with('success', 'Note ajoutée.');
    }

    public function consumePart(ConsumePartRequest $request, Ticket $ticket): RedirectResponse
    {
        $part = StockDepot::findOrFail($request->part_id);
        $this->ticketService->consumePart($ticket, $part, $request->quantity, $request->user());
        return back()->with('success', 'Pièce consommée.');
    }
}
