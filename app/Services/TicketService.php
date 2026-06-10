<?php

namespace App\Services;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\StockDepot;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketPart;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(private StockService $stockService) {}

    public function create(array $data, User $by)
    {
        return DB::transaction(function () use ($data, $by) {
            $ticket = Ticket::create($data);

            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $by->id,
                'type' => TicketEventType::StatusChanged->value,
                'note' => 'Ticket créé',
                'metadata' => ['to' => TicketStatus::Received->value],
            ]);

            return $ticket;
        });
    }

    public function transition(Ticket $ticket, TicketStatus $newStatus, User $by, string $note = ''): void
    {
        if (! $ticket->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Transition impossible : {$ticket->status->value} → {$newStatus->value}"
            );
        }

        DB::transaction(function () use ($ticket, $newStatus, $by, $note) {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => $newStatus,
                'closed_at' => $newStatus->isClosed() ? now() : null,
            ]);

            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $by->id,
                'type' => TicketEventType::StatusChanged->value,
                'note' => $note ?: 'Transition vers '.$newStatus->label(),
                'metadata' => [
                    'from' => $oldStatus->value,
                    'to' => $newStatus->value,
                ],
            ]);
        });
    }

    public function addNote(Ticket $ticket, string $note, User $by): void
    {
        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $by->id,
            'type' => TicketEventType::NoteAdded->value,
            'note' => $note,
        ]);
    }

    public function setDiagnosis(Ticket $ticket, string $diagnosis, ?float $price, User $by): void
    {
        DB::transaction(function () use ($ticket, $diagnosis, $price, $by) {
            $ticket->update([
                'diagnosis' => $diagnosis,
                'estimated_price' => $price,
            ]);

            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $by->id,
                'type' => TicketEventType::DiagnosisSet->value,
                'note' => $diagnosis,
                'metadata' => ['estimated_price' => $price],
            ]);
        });
    }

    public function consumePart(Ticket $ticket, StockDepot $stockPart, int $qty, User $by): void
    {
        DB::transaction(function () use ($ticket, $stockPart, $qty, $by) {
            $this->stockService->consume($stockPart, $qty, $ticket->id, $by);

            TicketPart::create([
                'ticket_id' => $ticket->id,
                'stock_depot_id' => $stockPart->id,
                'quantity' => $qty,
                'unit_price' => $stockPart->part->unit_price,
            ]);

            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $by->id,
                'type' => TicketEventType::PartConsumed->value,
                'note' => "{$qty}x {$stockPart->part->name}",
                'metadata' => ['stock_depot_id' => $stockPart->id, 'quantity' => $qty],
            ]);
        });
    }

    public function assignTechnician(Ticket $ticket, User $technicien, User $by): void
    {
        DB::transaction(function () use ($ticket, $technicien, $by) {
            $ticket->update(['technician_id' => $technicien->id]);

            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $by->id,
                'type' => TicketEventType::TechAssigned->value,
                'note' => "Assigné à {$technicien->name}",
                'metadata' => ['technician_id' => $technicien->id],
            ]);
        });
    }
}
