<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'brand_compat' => $this->brand_compat,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'unit_price' => $this->unit_price,
            'sell_price' => $this->sell_price,
            'stock_depots' => $this->whenLoaded('stockDepots', fn () => $this->stockDepots->map(fn ($sd) => [
                'id' => $sd->id,
                'depot_id' => $sd->depot_id,
                'depot_name' => $sd->relationLoaded('depot') ? $sd->depot->name : null,
                'quantity' => $sd->quantity,
                'alert_quantity' => $sd->alert_quantity,
                'is_critical' => $sd->is_critical,
            ])),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
