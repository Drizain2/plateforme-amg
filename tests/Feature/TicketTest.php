<?php

use App\Enums\InvoiceStatus;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Device;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

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

    $this->technicien->depots()->attach($this->depot->id);
});

// ── Création ─────────────────────────────────────────────────────────────────

test('un admin peut créer un ticket avec un client existant', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $device = Device::factory()->create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
    ]);

    $response = $this->actingAs($this->admin)->post(route('tickets.store'), [
        'customer_id' => $customer->id,
        'device_id' => $device->id,
        'depot_id' => $this->depot->id,
        'priority' => 'normal',
        'description' => 'Écran cassé, ne répond plus au toucher',
    ]);

    $response->assertRedirect(route('tickets.index'));
    $this->assertDatabaseHas('tickets', [
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => TicketStatus::Received->value,
    ]);
});

test('un ticket créé génère automatiquement une référence SAV-YYYY-NNNNN', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $device = Device::factory()->create(['shop_id' => $this->shop->id, 'customer_id' => $customer->id]);

    $this->actingAs($this->admin)->post(route('tickets.store'), [
        'customer_id' => $customer->id,
        'device_id' => $device->id,
        'depot_id' => $this->depot->id,
        'priority' => 'normal',
        'description' => 'Test référence',
    ]);

    $ticket = Ticket::withoutGlobalScopes()->where('shop_id', $this->shop->id)->latest()->first();
    expect($ticket->reference)->toMatch('/^SAV-\d{4}-\d{5}$/');
});

test('un utilisateur non authentifié ne peut pas créer un ticket', function () {
    $response = $this->post(route('tickets.store'), [
        'customer_name' => 'Jean Test',
        'device_type' => 'smartphone',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone 13',
        'depot_id' => $this->depot->id,
        'priority' => 'normal',
        'description' => 'Test',
    ]);

    $response->assertRedirect(route('login'));
});

// ── Transitions ──────────────────────────────────────────────────────────────

test('un technicien peut passer un ticket de reçu à diagnostic', function () {
    Notification::fake();

    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->technicien)
        ->post(route('tickets.transition', $ticket), [
            'status' => TicketStatus::Diagnosing->value,
        ]);

    $response->assertRedirect();
    expect($ticket->fresh()->status)->toBe(TicketStatus::Diagnosing);
});

test('une transition invalide est rejetée et le statut reste inchangé', function () {
    Notification::fake();

    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    // Received → Done est invalide (pas dans les transitions autorisées)
    $this->actingAs($this->admin)
        ->post(route('tickets.transition', $ticket), [
            'status' => TicketStatus::Done->value,
        ]);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Received);
});

test('une caissière ne peut pas changer le statut d\'un ticket', function () {
    $caissiere = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    $caissiere->assignRole('caissiere');

    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($caissiere)
        ->post(route('tickets.transition', $ticket), [
            'status' => TicketStatus::Diagnosing->value,
        ]);

    $response->assertForbidden();
    expect($ticket->fresh()->status)->toBe(TicketStatus::Received);
});

// ── Diagnostic ───────────────────────────────────────────────────────────────

test('un technicien peut saisir un diagnostic avec devis', function () {
    $ticket = Ticket::factory()->diagnosing()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->technicien)
        ->post(route('tickets.diagnosis', $ticket), [
            'diagnosis' => 'Écran LCD défectueux suite à chute',
            'estimated_price' => 35000,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'diagnosis' => 'Écran LCD défectueux suite à chute',
        'estimated_price' => 35000,
    ]);
});

test('le diagnostic crée un événement dans l\'historique', function () {
    $ticket = Ticket::factory()->diagnosing()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $this->actingAs($this->technicien)
        ->post(route('tickets.diagnosis', $ticket), [
            'diagnosis' => 'Batterie HS',
            'estimated_price' => null,
        ]);

    $this->assertDatabaseHas('ticket_events', [
        'ticket_id' => $ticket->id,
        'type' => 'diagnosis_set',
    ]);
});

test('une caissière ne peut pas saisir de diagnostic', function () {
    $caissiere = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    $caissiere->assignRole('caissiere');

    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($caissiere)
        ->post(route('tickets.diagnosis', $ticket), [
            'diagnosis' => 'Tentative non autorisée',
        ]);

    $response->assertForbidden();
});

// ── Assignation technicien ───────────────────────────────────────────────────

test('un admin peut assigner un technicien à un ticket', function () {
    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('tickets.assign', $ticket), [
            'technician_id' => $this->technicien->id,
        ]);

    $response->assertRedirect();
    expect($ticket->fresh()->technicien_id)->toBe($this->technicien->id);
});

test('l\'assignation crée un événement tech_assigned', function () {
    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('tickets.assign', $ticket), [
            'technician_id' => $this->technicien->id,
        ]);

    $this->assertDatabaseHas('ticket_events', [
        'ticket_id' => $ticket->id,
        'type' => 'tech_assigned',
    ]);
});

// ── Notes ────────────────────────────────────────────────────────────────────

test('un technicien peut ajouter une note', function () {
    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->technicien)
        ->post(route('tickets.notes.store', $ticket), [
            'note' => 'En attente de la pièce du fournisseur',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('ticket_events', [
        'ticket_id' => $ticket->id,
        'type' => 'note_added',
        'note' => 'En attente de la pièce du fournisseur',
    ]);
});

// ── Filtrage par rôle ────────────────────────────────────────────────────────

test('un technicien ne voit que ses propres tickets dans la liste', function () {
    $autretech = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $monTicket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'technicien_id' => $this->technicien->id,
    ]);

    Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'technicien_id' => $autretech->id,
    ]);

    $response = $this->actingAs($this->technicien)->get(route('tickets.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('tickets.meta.total', 1)
        ->where('tickets.data.0.id', $monTicket->id)
    );
});

test("la liste des techniciens n'est pas exposée à un technicien", function () {
    $response = $this->actingAs($this->technicien)->get(route('tickets.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('technicians', [])
    );
});

test('un admin voit tous les tickets et reçoit la liste des techniciens', function () {
    Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'technicien_id' => $this->technicien->id,
    ]);

    Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'technicien_id' => null,
    ]);

    $response = $this->actingAs($this->admin)->get(route('tickets.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('tickets.meta.total', 2)
        ->has('technicians', 1)
    );
});

// ── Consommation de pièce ────────────────────────────────────────────────────

test('un technicien peut consommer une pièce sur un ticket', function () {
    Notification::fake();

    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    $stock = StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 5,
        'alert_quantity' => 0,
    ]);

    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->technicien)
        ->post(route('tickets.parts.store', $ticket), [
            'part_id' => $stock->id,
            'quantity' => 2,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('ticket_parts', [
        'ticket_id' => $ticket->id,
        'stock_depot_id' => $stock->id,
        'quantity' => 2,
    ]);
});

test('la consommation décrémente le stock du dépôt', function () {
    Notification::fake();

    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    $stock = StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 5,
        'alert_quantity' => 0,
    ]);

    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $this->actingAs($this->technicien)
        ->post(route('tickets.parts.store', $ticket), [
            'part_id' => $stock->id,
            'quantity' => 3,
        ]);

    expect($stock->fresh()->quantity)->toBe(2);
});

test('la consommation crée un événement part_consumed', function () {
    Notification::fake();

    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    $stock = StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 5,
        'alert_quantity' => 0,
    ]);

    $ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $this->actingAs($this->technicien)
        ->post(route('tickets.parts.store', $ticket), [
            'part_id' => $stock->id,
            'quantity' => 1,
        ]);

    $this->assertDatabaseHas('ticket_events', [
        'ticket_id' => $ticket->id,
        'type' => 'part_consumed',
    ]);
});

// ── Facture depuis ticket ────────────────────────────────────────────────────

test('un admin peut créer une facture depuis un ticket terminé', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $ticket = Ticket::factory()->done()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'customer_id' => $customer->id,
        'estimated_price' => 25000,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('tickets.invoice', $ticket));

    $response->assertRedirect();
    $this->assertDatabaseHas('invoices', [
        'ticket_id' => $ticket->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft->value,
    ]);
});

test('une facture créée depuis un ticket inclut la ligne main d\'œuvre', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $ticket = Ticket::factory()->done()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'customer_id' => $customer->id,
        'estimated_price' => 15000,
    ]);

    $this->actingAs($this->admin)
        ->post(route('tickets.invoice', $ticket));

    $invoice = Invoice::where('ticket_id', $ticket->id)->first();
    $this->assertDatabaseHas('invoice_lines', [
        'invoice_id' => $invoice->id,
        'type' => 'service',
        'unit_price' => 15000,
    ]);
});

test('une facture depuis ticket porte le bon ticket_id et customer_id', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $ticket = Ticket::factory()->done()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('tickets.invoice', $ticket));

    $this->assertDatabaseHas('invoices', [
        'ticket_id' => $ticket->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft->value,
    ]);
});

// ── Isolation multi-tenant ───────────────────────────────────────────────────

test('les tickets d\'un autre shop n\'apparaissent pas dans la liste', function () {
    $autreShop = Shop::factory()->create();
    $autreDepot = Depot::factory()->create(['shop_id' => $autreShop->id]);
    $ticketAutre = Ticket::factory()->create([
        'shop_id' => $autreShop->id,
        'depot_id' => $autreDepot->id,
    ]);

    $monTicket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->admin)->get(route('tickets.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('tickets.data.0.id', $monTicket->id)
        ->missing('tickets.data.1')
    );
});
