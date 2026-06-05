<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'note' => $this->note,
            'stock' => $this->whenLoaded('stock', fn () => [
                'id' => $this->stock->id,
                'part' => $this->stock->relationLoaded('part') ? [
                    'id' => $this->stock->part->id,
                    'name' => $this->stock->part->name,
                    'sku' => $this->stock->part->sku,
                ] : null,
            ]),
            'depot' => $this->whenLoaded('depot', fn () => [
                'id' => $this->depot->id,
                'name' => $this->depot->name,
            ]),
            'transfer_depot' => $this->whenLoaded('transferDepot', fn () => $this->transferDepot ? [
                'id' => $this->transferDepot->id,
                'name' => $this->transferDepot->name,
            ] : null),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
