<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\Shop;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            app()->instance('current_shop', $shop);

            $depot = Depot::where('shop_id', $shop->id)->first();

            if (! $depot) {
                return;
            }

            $technician = User::role('technicien')->where('shop_id', $shop->id)->first();

            // Tickets en cours — différents statuts
            Ticket::factory()->count(3)
                ->state(['shop_id' => $shop->id, 'depot_id' => $depot->id])
                ->create();

            Ticket::factory()->diagnosing()->count(2)
                ->state(['shop_id' => $shop->id, 'depot_id' => $depot->id])
                ->when($technician, fn ($f) => $f->withTechnician($technician))
                ->create();

            Ticket::factory()->repairing()->count(2)
                ->state(['shop_id' => $shop->id, 'depot_id' => $depot->id])
                ->when($technician, fn ($f) => $f->withTechnician($technician))
                ->create();

            Ticket::factory()->urgent()
                ->state(['shop_id' => $shop->id, 'depot_id' => $depot->id])
                ->create();

            // Tickets clôturés
            Ticket::factory()->returned()->count(4)
                ->state(['shop_id' => $shop->id, 'depot_id' => $depot->id])
                ->create();
        });
    }
}
