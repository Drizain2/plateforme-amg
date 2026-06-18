<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isTechnicien = $this->roles->first()?->name === 'technicien';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->roles->first()?->name,
            'is_active' => $this->is_active,
            'depot_ids' => $this->depots->pluck('id'),
            'depots' => $this->depots->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
            ]),
            'tickets_count' => $isTechnicien
                ? $this->whenCounted('assignedTickets')
                : $this->whenCounted('tickets'),
            'tickets_count_label' => $isTechnicien ? 'assigné(s)' : 'créé(s)',
            'created_at' => $this->created_at->format('d/m/Y'),
        ];
    }
}
