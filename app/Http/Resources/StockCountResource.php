<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockCountResource extends JsonResource
{
    /**
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
            'note' => $this->note,
            'started_at' => $this->started_at?->format('d/m/Y H:i'),
            'validated_at' => $this->validated_at?->format('d/m/Y H:i'),
            'lines_count' => $this->lines_count,
            'depot' => $this->whenLoaded('depot', fn () => [
                'id' => $this->depot->id,
                'name' => $this->depot->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($l) => [
                'id' => $l->id,
                'part' => $l->stockDepot?->part ? [
                    'id' => $l->stockDepot->part->id,
                    'name' => $l->stockDepot->part->name,
                    'sku' => $l->stockDepot->part->sku,
                ] : null,
                'expected_quantity' => $l->expected_quantity,
                'counted_quantity' => $l->counted_quantity,
                'difference' => $l->difference,
                'unit_cost' => $l->unit_cost,
                'note' => $l->note,
            ])),
        ];
    }
}
