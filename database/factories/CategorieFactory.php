<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categorie>
 */
class CategorieFactory extends Factory
{
     private static array $categories = [
        'Écran', 'Batterie', 'Connecteur de charge', 'Caméra',
        'Haut-parleur', 'Microphone', 'Nappe', 'Châssis', 'Vitre arrière',
    ];
    public function definition(): array
    {
         return [
            'shop_id' => Shop::factory(),
            'name' => fake()->unique()->randomElement(self::$categories),
            'is_active' => true,
        ];
    }
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
