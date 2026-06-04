<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=>$this->id,
            "stock_id"=> $this->whenLoaded('stock_depot', fn()=> $this->stock_depot),
            "depot_id"=> $this->whenLoaded('depot', fn()=> $this->depot),
            "part_id"=> $this->whenLoaded('part', fn()=> $this->part),
            "quantity"=> $this->quantity,
            "type"=> $this->type,
            "note"=> $this->note,
            "user_id"=> $this->user_id,
            "user"=> $this->whenLoaded('user', fn()=> [
                "id"=> $this->user->id,
                "name"=> $this->user->name,
                "email"=> $this->user->email,
                "role"=> $this->user->role,
            ]),
            "transfert_depot"=> $this->whenLoaded('transferDepot', fn()=> [
                "id"=> $this->transferDepot?->id,
                "name"=> $this->transferDepot?->name
            ]),
            "created_at"=> $this->created_at,
            "updated_at"=> $this->updated_at,
        ];
    }
}
