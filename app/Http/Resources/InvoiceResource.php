<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'number' => $this->number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'total_ht' => $this->total_ht,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'total_ttc' => $this->total_ttc,
            'notes' => $this->notes,
            'issued_at' => $this->issued_at?->format('d/m/Y'),
            'due_at' => $this->due_at?->format('d/m/Y'),
            'paid_at' => $this->paid_at?->format('d/m/Y'),
            'can_edit' => $this->status === InvoiceStatus::Draft,
            'next_statuses' => $this->nextStatuses(),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ]),
            'ticket' => $this->whenLoaded('ticket', fn () => $this->ticket ? [
                'id' => $this->ticket->id,
                'reference' => $this->ticket->reference,
            ] : null),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($l) => [
                'id' => $l->id,
                'type' => $l->type,
                'label' => $l->label,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'total' => $l->total,
            ])
            ),
        ];
    }

    private function nextStatuses(): array
    {
        return collect(InvoiceStatus::cases())
            ->filter(fn ($s) => $this->status->canTransitionTo($s))
            ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])
            ->values()
            ->toArray();
    }
}
