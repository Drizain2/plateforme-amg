<?php

use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Shop;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->admin = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $this->technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
});

// ── Création ─────────────────────────────────────────────────────────────────

test('un admin peut créer un client', function () {
    $response = $this->actingAs($this->admin)->post(route('customers.store'), [
        'name' => 'Jean Dupont',
        'email' => 'jean@example.com',
        'phone' => '+224 620 000 001',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'shop_id' => $this->shop->id,
        'name' => 'Jean Dupont',
        'email' => 'jean@example.com',
    ]);
});

test("l'email doit être unique au sein du même atelier", function () {
    Customer::factory()->create([
        'shop_id' => $this->shop->id,
        'email' => 'doublon@example.com',
    ]);

    $response = $this->actingAs($this->admin)->post(route('customers.store'), [
        'name' => 'Autre Client',
        'email' => 'doublon@example.com',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('customers', 1);
});

test('le même email peut être utilisé sur un autre atelier', function () {
    $autreShop = Shop::factory()->create();
    Customer::factory()->create([
        'shop_id' => $autreShop->id,
        'email' => 'partage@example.com',
    ]);

    $response = $this->actingAs($this->admin)->post(route('customers.store'), [
        'name' => 'Mon Client',
        'email' => 'partage@example.com',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('customers', 2);
});

// ── Modification ─────────────────────────────────────────────────────────────

test('un admin peut modifier un client', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->admin)->put(route('customers.update', $customer), [
        'name' => 'Nom Modifié',
        'phone' => '+224 620 000 999',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Nom Modifié',
    ]);
});

test("la modification ignore l'email si inchangé", function () {
    $customer = Customer::factory()->create([
        'shop_id' => $this->shop->id,
        'email' => 'moi@example.com',
    ]);

    $response = $this->actingAs($this->admin)->put(route('customers.update', $customer), [
        'name' => 'Prénom Modifié',
        'email' => 'moi@example.com',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Prénom Modifié']);
});

// ── Suppression ───────────────────────────────────────────────────────────────

test('un admin peut supprimer un client sans tickets actifs', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->admin)->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});

test('la suppression est bloquée si le client a des tickets ouverts', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'customer_id' => $customer->id,
        'status' => TicketStatus::Received,
    ]);

    $response = $this->actingAs($this->admin)->delete(route('customers.destroy', $customer));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});

test('un technicien ne peut pas supprimer un client', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->technicien)->delete(route('customers.destroy', $customer));

    $response->assertForbidden();
    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});

// ── Isolation multi-tenant ────────────────────────────────────────────────────

test("les clients d'un autre atelier ne sont pas accessibles", function () {
    $autreShop = Shop::factory()->create();
    $clientAutre = Customer::factory()->create(['shop_id' => $autreShop->id]);

    $response = $this->actingAs($this->admin)->get(route('customers.show', $clientAutre));

    $response->assertNotFound();
});

// ── Recherche ────────────────────────────────────────────────────────────────

test('la recherche retourne les clients correspondants', function () {
    Customer::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Jean Dupont']);
    Customer::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Marie Camara']);

    $response = $this->actingAs($this->admin)->getJson(route('customers.search', ['q' => 'jean']));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['name' => 'Jean Dupont']);
});

test("la recherche ne retourne pas les clients d'un autre atelier", function () {
    $autreShop = Shop::factory()->create();
    Customer::factory()->create(['shop_id' => $autreShop->id, 'name' => 'Client Autre Atelier']);
    Customer::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Mon Client']);

    $response = $this->actingAs($this->admin)->getJson(route('customers.search', ['q' => 'client']));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['name' => 'Mon Client']);
});
