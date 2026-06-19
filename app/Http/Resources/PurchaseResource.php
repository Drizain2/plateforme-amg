<?php

namespace App\Http\Resources;

use App\Enums\PurchaseStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
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
            'ordered_at' => $this->ordered_at?->format('d/m/Y'),
            'received_at' => $this->received_at?->format('d/m/Y'),
            'paid_at' => $this->paid_at?->format('d/m/Y'),
            'next_statuses' => $this->nextStatuses(),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'email' => $this->supplier->email,
                'phone' => $this->supplier->phone,
            ]),
            'depot' => $this->whenLoaded('depot', fn () => [
                'id' => $this->depot->id,
                'name' => $this->depot->name,
            ]),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($l) => [
                'id' => $l->id,
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
        return collect(PurchaseStatus::cases())
            ->filter(fn ($s) => $this->status->canTransitionTo($s))
            ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])
            ->values()
            ->toArray();
    }
}
