<?php

namespace App\Http\Controllers;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Inertia\Inertia;
use Inertia\Response;

class TrackController extends Controller
{
    public function show(string $token): Response
    {
        $ticket = Ticket::withoutGlobalScopes()
            ->where('tracking_token', $token)
            ->with([
                'device:id,brand,model,type',
                'shop:id,name,email,phone',
                'events' => fn ($q) => $q
                    ->whereIn('type', [
                        TicketEventType::StatusChanged->value,
                        TicketEventType::NoteAdded->value,
                        TicketEventType::DiagnosisSet->value,
                    ])
                    ->latest(),
            ])
            ->firstOrFail();

        return Inertia::render('Track/Show', [
            'ticket' => [
                'reference' => $ticket->reference,
                'tracking_token' => $ticket->tracking_token,
                'status' => $ticket->status->value,
                'status_label' => $ticket->status->label(),
                'status_color' => $ticket->status->color(),
                'priority_label' => $ticket->priority->label(),
                'description' => $ticket->description,
                'diagnosis' => $ticket->diagnosis,
                'estimated_price' => $ticket->estimated_price,
                'estimated_return_date' => $ticket->estimated_return_date?->format('d/m/Y'),
                'closed_at' => $ticket->closed_at?->format('d/m/Y'),
                'created_at' => $ticket->created_at->format('d/m/Y'),
                'device' => [
                    'full_name' => $ticket->device->full_name,
                    'type' => $ticket->device->type,
                    'color' => $ticket->device->color,
                    'serial_number' => $ticket->device->serial_number,
                ],
                'shop' => [
                    'name' => $ticket->shop->name,
                    'email' => $ticket->shop->email,
                    'phone' => $ticket->shop->phone,
                ],
                'events' => $ticket->events->map(fn ($e) => [
                    'type' => $e->type,
                    'note' => $e->note,
                    'metadata' => $e->metadata,
                    'created_at' => $e->created_at->format('d/m/Y H:i'),
                ]),
                'steps' => $this->buildSteps($ticket),
            ],
        ]);
    }

    private function buildSteps(Ticket $ticket): array
    {
        $allStatuses = [
            TicketStatus::Received,
            TicketStatus::Diagnosing,
            TicketStatus::Repairing,
            TicketStatus::Done,
            TicketStatus::Returned,
        ];

        $currentIndex = collect($allStatuses)
            ->search(fn ($s) => $s === $ticket->status);

        // Si annulé, on garde les étapes passées
        $isCancelled = $ticket->status === TicketStatus::Cancelled;

        return collect($allStatuses)->map(fn ($status, $index) => [
            'label' => $status->label(),
            'status' => $status->value,
            'state' => match (true) {
                $isCancelled && $index === $currentIndex => 'cancelled',
                $index < ($currentIndex ?: 0) => 'done',
                $index === ($currentIndex ?: 0) => 'current',
                default => 'pending',
            },
        ])->toArray();
    }
}
