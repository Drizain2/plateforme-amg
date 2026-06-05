<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockDepotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'alert_quantity' => $this->alert_quantity,
            'is_critical' => $this->is_critical,
            'part' => $this->whenLoaded('part', fn () => [
                'id' => $this->part->id,
                'name' => $this->part->name,
                'sku' => $this->part->sku,
            ]),
            'depot' => $this->whenLoaded('depot', fn () => [
                'id' => $this->depot->id,
                'name' => $this->depot->name,
            ]),
        ];
    }
}
