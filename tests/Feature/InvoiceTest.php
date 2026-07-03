<?php

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\InvoiceSent;
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

    $this->caissiere = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    $this->caissiere->assignRole('caissiere');

    app()->instance('current_shop', $this->shop);
});

// ── Accès ──────────────────────────────────────────────────────────────────

test('un admin peut voir la liste des factures', function () {
    $response = $this->actingAs($this->admin)->get(route('invoices.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/Index')
        ->has('invoices')
        ->has('summary')
    );
});

test('un technicien peut voir la liste des factures', function () {
    $response = $this->actingAs($this->technicien)->get(route('invoices.index'));

    $response->assertOk();
});

test('un visiteur non authentifié est redirigé vers login', function () {
    $response = $this->get(route('invoices.index'));

    $response->assertRedirect(route('login'));
});

test('un admin peut voir le détail d\'une facture', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('invoices.show', $invoice));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Invoices/Show')
        ->where('invoice.id', $invoice->id)
    );
});

// ── Isolation multi-tenant ────────────────────────────────────────────────

test("une facture d'un autre atelier retourne 404", function () {
    $autreShop = Shop::factory()->create();
    $autreCustomer = Customer::factory()->create(['shop_id' => $autreShop->id]);

    app()->instance('current_shop', $autreShop);
    $invoice = Invoice::create([
        'shop_id' => $autreShop->id,
        'customer_id' => $autreCustomer->id,
        'status' => InvoiceStatus::Draft,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);
    app()->instance('current_shop', $this->shop);

    $response = $this->actingAs($this->admin)->get(route('invoices.show', $invoice));

    $response->assertNotFound();
});

// ── Création ───────────────────────────────────────────────────────────────

test('un admin peut créer une facture avec un client existant', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->admin)->post(route('invoices.store'), [
        'customer_id' => $customer->id,
        'tax_rate' => 20,
        'lines' => [
            ['type' => 'service', 'label' => "Main d'oeuvre", 'quantity' => 1, 'unit_price' => 30000],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('invoices', [
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft->value,
    ]);
});

test('la création calcule correctement le total TTC', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->admin)->post(route('invoices.store'), [
        'customer_id' => $customer->id,
        'tax_rate' => 20,
        'lines' => [
            ['type' => 'service', 'label' => 'Réparation', 'quantity' => 1, 'unit_price' => 50000],
            ['type' => 'service', 'label' => 'Diagnostic', 'quantity' => 1, 'unit_price' => 10000],
        ],
    ]);

    $invoice = Invoice::where('shop_id', $this->shop->id)->latest()->first();
    // HT = 60000, TVA 20% = 12000, TTC = 72000
    expect((float) $invoice->total_ttc)->toBe(72000.0);
});

test('la création sans ligne est rejetée par la validation', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->admin)->post(route('invoices.store'), [
        'customer_id' => $customer->id,
        'tax_rate' => 20,
        'lines' => [],
    ]);

    $response->assertSessionHasErrors('lines');
});

test('un numéro de facture unique est généré automatiquement', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->admin)->post(route('invoices.store'), [
        'customer_id' => $customer->id,
        'tax_rate' => 20,
        'lines' => [
            ['type' => 'service', 'label' => 'Service', 'quantity' => 1, 'unit_price' => 10000],
        ],
    ]);

    $invoice = Invoice::where('shop_id', $this->shop->id)->latest()->first();
    expect($invoice->number)->toMatch('/^FAC-\d{4}-\d+$/');
});

// ── Modification ───────────────────────────────────────────────────────────

test("un admin peut modifier les métadonnées d'une facture brouillon", function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->put(route('invoices.update', $invoice), [
        'tax_rate' => 18,
        'notes' => 'Note de test',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'tax_rate' => 18,
        'notes' => 'Note de test',
    ]);
});

test('modifier une facture non brouillon est refusé', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Sent,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->put(route('invoices.update', $invoice), [
        'tax_rate' => 10,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'tax_rate' => 20]);
});

test('un technicien ne peut pas modifier une facture', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->technicien)->put(route('invoices.update', $invoice), [
        'tax_rate' => 10,
    ]);

    $response->assertForbidden();
});

// ── Lignes ──────────────────────────────────────────────────────────────────

test('un admin peut ajouter une ligne service à une facture brouillon', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->post(route('invoices.lines.store', $invoice), [
        'type' => 'service',
        'label' => "Main d'oeuvre additionnelle",
        'quantity' => 2,
        'unit_price' => 15000,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('invoice_lines', [
        'invoice_id' => $invoice->id,
        'label' => "Main d'oeuvre additionnelle",
        'quantity' => 2,
        'unit_price' => 15000,
    ]);
});

test("un admin peut supprimer une ligne d'une facture brouillon", function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);
    $line = InvoiceLine::create([
        'invoice_id' => $invoice->id,
        'type' => 'service',
        'label' => 'Ligne à supprimer',
        'quantity' => 1,
        'unit_price' => 5000,
    ]);

    $response = $this->actingAs($this->admin)->delete(route('invoices.lines.destroy', [$invoice, $line]));

    $response->assertRedirect();
    $this->assertDatabaseMissing('invoice_lines', ['id' => $line->id]);
});

// ── Transitions de statut ──────────────────────────────────────────────────

test('un admin peut passer une facture de brouillon à envoyée', function () {
    Notification::fake();

    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('invoices.transition', $invoice), ['status' => InvoiceStatus::Sent->value]);

    $response->assertRedirect();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
});

test('un admin peut marquer une facture comme payée', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Sent,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('invoices.transition', $invoice), ['status' => InvoiceStatus::Paid->value]);

    $response->assertRedirect();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    expect($invoice->fresh()->paid_at)->not->toBeNull();
});

test('une caissière peut marquer une facture comme payée', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Sent,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->caissiere)
        ->post(route('invoices.transition', $invoice), ['status' => InvoiceStatus::Paid->value]);

    $response->assertRedirect();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('un technicien ne peut pas marquer une facture comme payée', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Sent,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->technicien)
        ->post(route('invoices.transition', $invoice), ['status' => InvoiceStatus::Paid->value]);

    $response->assertForbidden();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
});

test("l'envoi notifie le client par email", function () {
    Notification::fake();

    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $invoice = Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('invoices.transition', $invoice), ['status' => InvoiceStatus::Sent->value]);

    Notification::assertSentTo($customer, InvoiceSent::class);
});
