<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepotResource extends JsonResource
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
            "adresse"=> $this->adresse,
            "phone"=> $this->phone,
            "is_active"=> $this->is_active,
            "stock_count"=> $this->whenLoaded('stock_depots', fn()=>$this->stock_depots->count()),
            "users"=> $this->whenLoaded('users', fn()=>$this->users->map(fn($user)=>[
                "id"=>$user->id,
                "name"=>$user->name,
                "email"=>$user->email,
                "phone"=>$user->phone,
            ])),
            "created_at"=> $this->created_at,
            "updated_at"=> $this->updated_at,
        ];
    }
}
