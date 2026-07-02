<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Device;
use App\Models\Shop;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    private static array $symptoms = [
        'Écran cassé, tactile ne répond plus',
        'Batterie ne charge plus, autonomie très faible',
        'Appareil photo flou malgré nettoyage',
        'Haut-parleur grésille à fort volume',
        'Bouton home bloqué, ne répond plus',
        'Appareil ne s\'allume plus après chute',
        'Connecteur de charge endommagé',
        'Micro ne fonctionne pas en appel',
    ];

    public function definition(): array
    {
        $shop = Shop::factory();
        $depot = Depot::factory()->state(['shop_id' => $shop]);
        $customer = Customer::factory()->state(['shop_id' => $shop]);
        $device = Device::factory()->state(['shop_id' => $shop, 'customer_id' => $customer]);

        return [
            'shop_id' => $shop,
            'depot_id' => $depot,
            'customer_id' => $customer,
            'device_id' => $device,
            'technicien_id' => null,
            'status' => TicketStatus::Received,
            'priority' => TicketPriority::Normal,
            'description' => fake()->randomElement(self::$symptoms),
            'diagnosis' => null,
            'estimated_price' => null,
            'estimated_return_date' => fake()->optional(0.5)->dateTimeBetween('+1 day', '+14 days'),
            'closed_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Ticket $ticket) {
            Customer::withoutGlobalScopes()->where('id', $ticket->customer_id)->update(['shop_id' => $ticket->shop_id]);
            Device::withoutGlobalScopes()->where('id', $ticket->device_id)->update(['shop_id' => $ticket->shop_id]);
        });
    }

    public function withTechnician(User $technician): static
    {
        return $this->state(['technicien_id' => $technician->id]);
    }

    public function diagnosing(): static
    {
        return $this->state(['status' => TicketStatus::Diagnosing]);
    }

    public function repairing(): static
    {
        return $this->state([
            'status' => TicketStatus::Repairing,
            'diagnosis' => fake()->sentence(),
            'estimated_price' => fake()->numberBetween(5000, 80000),
        ]);
    }

    public function done(): static
    {
        return $this->state([
            'status' => TicketStatus::Done,
            'diagnosis' => fake()->sentence(),
            'estimated_price' => fake()->numberBetween(5000, 80000),
        ]);
    }

    public function returned(): static
    {
        return $this->state([
            'status' => TicketStatus::Returned,
            'diagnosis' => fake()->sentence(),
            'estimated_price' => fake()->numberBetween(5000, 80000),
            'closed_at' => now(),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(['priority' => TicketPriority::Urgent]);
    }

    public function high(): static
    {
        return $this->state(['priority' => TicketPriority::High]);
    }
}
