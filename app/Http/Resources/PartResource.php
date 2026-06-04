<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "name"=> $this->name,
            "sku"=> $this->sku,
            "description"=> $this->description,
            "category"=> $this->whenLoaded('category', fn()=> [
                "id"=>$this->category->id,
                "name"=>$this->category->name
            ]),
            "supplier"=> $this->whenLoaded('supplier', fn()=> [
                "id"=>$this->supplier->id,
                "name"=>$this->supplier->name
            ]),
            "unit_price"=> $this->unit_price,
            "sell_price"=> $this->sell_price,
            "quantity"=> $this->quantity,
            "stock_depots"=> $this->whenLoaded('stock_depots', fn()=> [
                "id"=>$this->stock_depots->id,
                "quantity"=>$this->stock_depots->quantity,
                "is_critical"=>$this->stock_depots->is_critical
            ]),
            "is_active"=> $this->is_active,
            "created_at"=> $this->created_at,
            "updated_at"=> $this->updated_at,
        ];
    }
}
