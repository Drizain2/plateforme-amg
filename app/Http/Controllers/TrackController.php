<?php

namespace App\Http\Controllers;

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
                'shop:id,name,phone',
                'events' => fn ($q) => $q->with('user:id,name')->latest(),
            ])
            ->firstOrFail();

        return Inertia::render('Track/Show', [
            'ticket' => [
                'reference' => $ticket->reference,
                'status' => $ticket->status->value,
                'status_label' => $ticket->status->label(),
                'status_color' => $ticket->status->color(),
                'description' => $ticket->description,
                'diagnosis' => $ticket->diagnosis,
                'estimated_return_date' => $ticket->estimated_return_date?->format('d/m/Y'),
                'device' => $ticket->device ? "{$ticket->device->brand} {$ticket->device->model}" : null,
                'shop' => $ticket->shop
                    ? ['name' => $ticket->shop->name, 'phone' => $ticket->shop->phone]
                    : null,
                'events' => $ticket->events->map(fn ($e) => [
                    'id' => $e->id,
                    'type' => $e->type,
                    'note' => $e->note,
                    'by' => $e->user?->name,
                    'created_at' => $e->created_at->format('d/m/Y H:i'),
                ]),
            ],
        ]);
    }
}
