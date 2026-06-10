<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'address'        => $this->address,
            'notes'          => $this->notes,
            'tickets_count'  => $this->whenCounted('tickets'),
            'devices_count'  => $this->whenCounted('devices'),
            'total_spent'    => $this->when(
                isset($this->total_spent),
                fn() => $this->total_spent
            ),
            'tickets'        => $this->whenLoaded('tickets', fn() =>
                $this->tickets->map(fn($t) => [
                    'id'            => $t->id,
                    'reference'     => $t->reference,
                    'status_label'  => $t->status->label(),
                    'status_color'  => $t->status->color(),
                    'device_name'   => $t->device->full_name,
                    'created_at'    => $t->created_at->format('d/m/Y'),
                ])
            ),
            'devices'        => $this->whenLoaded('devices', fn() =>
                $this->devices->map(fn($d) => [
                    'id'            => $d->id,
                    'full_name'     => $d->full_name,
                    'type'          => $d->type,
                    'serial_number' => $d->serial_number,
                    'color'         => $d->color,
                ])
            ),
        ];
    }
}
