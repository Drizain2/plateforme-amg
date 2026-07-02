<?php

use App\Enums\InvoiceStatus;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Invoice;
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

    // current_shop nécessaire pour les scopes lors de la création directe
    app()->instance('current_shop', $this->shop);
});

// ── Accès ─────────────────────────────────────────────────────────────────────

test('un admin peut accéder au rapport financier', function () {
    $response = $this->actingAs($this->admin)->get(route('reports.cash'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Reports/Cash')
        ->has('summary')
        ->has('by_period')
        ->has('by_technician')
        ->has('top_clients')
        ->has('uninvoiced_tickets')
        ->has('filters')
    );
});

test('un technicien sans analytics ne peut pas accéder au rapport', function () {
    $response = $this->actingAs($this->technicien)->get(route('reports.cash'));

    $response->assertForbidden();
});

// ── Synthèse ──────────────────────────────────────────────────────────────────

test('la synthèse reflète les factures payées dans la période', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Paid,
        'total_ttc' => 50000,
        'tax_rate' => 20,
        'issued_at' => now()->startOfMonth(),
        'paid_at' => now(),
    ]);

    Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Paid,
        'total_ttc' => 30000,
        'tax_rate' => 20,
        'issued_at' => now()->startOfMonth(),
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.cash'));

    $response->assertInertia(fn ($page) => $page
        ->where('summary.revenue_paid', 80000)
        ->where('summary.invoices_paid_count', 2)
    );
});

test('les factures hors période ne sont pas comptabilisées', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    // Facture du mois dernier — hors de la période par défaut (mois en cours)
    Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Paid,
        'total_ttc' => 99000,
        'tax_rate' => 20,
        'issued_at' => now()->subMonth(),
        'paid_at' => now()->subMonth(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.cash'));

    $response->assertInertia(fn ($page) => $page
        ->where('summary.revenue_paid', 0)
        ->where('summary.invoices_paid_count', 0)
    );
});

// ── Tickets non facturés ──────────────────────────────────────────────────────

test('les tickets clôturés sans facture apparaissent dans uninvoiced_tickets', function () {
    $ticket = Ticket::factory()->done()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'status' => TicketStatus::Done,
        'closed_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.cash'));

    $response->assertInertia(fn ($page) => $page
        ->where('summary.uninvoiced_count', 1)
        ->has('uninvoiced_tickets', 1)
        ->where('uninvoiced_tickets.0.reference', $ticket->reference)
    );
});

test('un ticket avec facture ne figure pas dans uninvoiced_tickets', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);
    $ticket = Ticket::factory()->done()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'status' => TicketStatus::Done,
        'closed_at' => now(),
    ]);

    Invoice::create([
        'shop_id' => $this->shop->id,
        'ticket_id' => $ticket->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
        'total_ttc' => 10000,
        'tax_rate' => 20,
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.cash'));

    $response->assertInertia(fn ($page) => $page
        ->where('summary.uninvoiced_count', 0)
        ->has('uninvoiced_tickets', 0)
    );
});

// ── Période personnalisée ─────────────────────────────────────────────────────

test('le filtre de dates restreint les données retournées', function () {
    $customer = Customer::factory()->create(['shop_id' => $this->shop->id]);

    Invoice::create([
        'shop_id' => $this->shop->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Paid,
        'total_ttc' => 15000,
        'tax_rate' => 20,
        'issued_at' => now()->subYear(),
        'paid_at' => now()->subYear(),
    ]);

    $from = now()->subYear()->startOfYear()->toDateString();
    $to = now()->subYear()->endOfYear()->toDateString();

    $response = $this->actingAs($this->admin)->get(route('reports.cash', [
        'from' => $from,
        'to' => $to,
    ]));

    $response->assertInertia(fn ($page) => $page
        ->where('summary.revenue_paid', 15000)
        ->where('filters.from', $from)
        ->where('filters.to', $to)
    );
});
