<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'priority_color' => $this->priority->color(),
            'description' => $this->description,
            'diagnosis' => $this->diagnosis,
            'estimated_price' => $this->estimated_price,
            'estimated_return_date' => $this->estimated_return_date?->format('d/m/Y'),
            'closed_at' => $this->closed_at?->format('d/m/Y H:i'),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'next_statuses' => array_map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ], $this->status->transitions()),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ]),
            'device' => $this->whenLoaded('device', fn () => [
                'id' => $this->device->id,
                'full_name' => $this->device->full_name,
                'type' => $this->device->type,
                'brand' => $this->device->brand,
                'model' => $this->device->model,
                'serial_number' => $this->device->serial_number,
                'color' => $this->device->color,
                'condition_in' => $this->device->condition_in,
            ]),
            'technicien' => $this->whenLoaded('technicien', fn () => $this->technicien ? [
                'id' => $this->technicien->id,
                'name' => $this->technicien->name,
            ] : null),
            'depot' => $this->whenLoaded('depot', fn () => [
                'id' => $this->depot->id,
                'name' => $this->depot->name,
            ]),
            'events' => $this->whenLoaded(
                'events',
                fn () => $this->events->map(fn ($e) => [
                    'id' => $e->id,
                    'type' => $e->type,
                    'note' => $e->note,
                    'metadata' => $e->metadata,
                    'created_at' => $e->created_at->format('d/m/Y H:i'),
                    'user' => $e->user ? ['id' => $e->user->id, 'name' => $e->user->name] : null,
                ])
            ),
            'parts' => $this->whenLoaded(
                'parts',
                fn () => $this->parts->map(fn ($p) => [
                    'id' => $p->id,
                    'quantity' => $p->quantity,
                    'unit_price' => $p->unit_price,
                    'total' => $p->total,
                    'part' => ['id' => $p->part->id, 'name' => $p->part->name],
                ])
            ),
            'invoice_id' => $this->whenLoaded('invoice', fn () => $this->invoice?->id),
            'tracking_token' => $this->tracking_token,
        ];
    }
}
