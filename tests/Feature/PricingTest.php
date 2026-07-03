<?php

use App\Models\Plan;

// ── Page publique pricing ─────────────────────────────────────────────────────

test('la page pricing est accessible sans authentification', function () {
    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Pricing'));
});

test('la page pricing affiche les plans actifs', function () {
    Plan::factory()->create(['name' => 'Unique Plan Alpha', 'is_active' => true, 'price' => 5000]);
    Plan::factory()->create(['name' => 'Unique Plan Beta', 'is_active' => true, 'price' => 10000]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->where('plans', fn ($plans) => collect($plans)->pluck('name')->contains('Unique Plan Alpha') &&
            collect($plans)->pluck('name')->contains('Unique Plan Beta')
        ));
});

test('les plans inactifs ne sont pas affichés sur la page pricing', function () {
    Plan::factory()->create(['name' => 'Legacy', 'is_active' => false]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->where('plans', fn ($plans) => collect($plans)->pluck('name')->doesntContain('Legacy')
        ));
});
