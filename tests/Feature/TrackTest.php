<?php

use App\Enums\TicketStatus;
use App\Models\Depot;
use App\Models\Shop;
use App\Models\Ticket;
use App\Models\TicketEvent;
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

    $this->ticket = Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
    ]);
});

// ── Accessibilité publique ────────────────────────────────────────────────────

test('la page de suivi est accessible sans authentification', function () {
    $response = $this->get(route('track', $this->ticket->tracking_token));

    $response->assertOk();
});

test('un token valide affiche les informations du ticket', function () {
    $response = $this->get(route('track', $this->ticket->tracking_token));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Track/Show')
        ->where('ticket.reference', $this->ticket->reference)
        ->where('ticket.status', $this->ticket->status->value)
        ->has('ticket.steps')
        ->has('ticket.events')
    );
});

test('un token invalide retourne 404', function () {
    $response = $this->get(route('track', 'token-inexistant'));

    $response->assertNotFound();
});

// ── Filtrage des événements ───────────────────────────────────────────────────

test('les événements de statut et de note sont exposés', function () {
    TicketEvent::create([
        'ticket_id' => $this->ticket->id,
        'shop_id' => $this->shop->id,
        'user_id' => $this->admin->id,
        'type' => 'status_changed',
        'note' => 'Passage en diagnostic',
        'metadata' => ['from' => 'received', 'to' => 'diagnosing'],
    ]);

    TicketEvent::create([
        'ticket_id' => $this->ticket->id,
        'shop_id' => $this->shop->id,
        'user_id' => $this->admin->id,
        'type' => 'note_added',
        'note' => 'En attente pièce',
    ]);

    $response = $this->get(route('track', $this->ticket->tracking_token));

    $response->assertInertia(fn ($page) => $page
        ->has('ticket.events', 2)
    );
});

test('les événements internes ne sont pas exposés au client', function () {
    // tech_assigned et part_consumed sont des détails internes
    TicketEvent::create([
        'ticket_id' => $this->ticket->id,
        'shop_id' => $this->shop->id,
        'user_id' => $this->admin->id,
        'type' => 'tech_assigned',
        'note' => 'Technicien assigné',
    ]);

    TicketEvent::create([
        'ticket_id' => $this->ticket->id,
        'shop_id' => $this->shop->id,
        'user_id' => $this->admin->id,
        'type' => 'part_consumed',
        'note' => 'Écran LCD consommé',
    ]);

    $response = $this->get(route('track', $this->ticket->tracking_token));

    $response->assertInertia(fn ($page) => $page
        ->has('ticket.events', 0)
    );
});

// ── Stepper ───────────────────────────────────────────────────────────────────

test('le stepper marque les étapes correctement pour un ticket reçu', function () {
    $response = $this->get(route('track', $this->ticket->tracking_token));

    $response->assertInertia(fn ($page) => $page
        ->where('ticket.steps.0.status', 'received')
        ->where('ticket.steps.0.state', 'current')
        ->where('ticket.steps.1.state', 'pending')
    );
});

test('le stepper montre les étapes passées pour un ticket en cours de réparation', function () {
    $this->ticket->update(['status' => TicketStatus::Repairing]);

    $response = $this->get(route('track', $this->ticket->tracking_token));

    $response->assertInertia(fn ($page) => $page
        ->where('ticket.steps.0.state', 'done')    // received
        ->where('ticket.steps.1.state', 'done')    // diagnosing
        ->where('ticket.steps.2.state', 'current') // repairing
        ->where('ticket.steps.3.state', 'pending') // done
    );
});

// ── Informations atelier ──────────────────────────────────────────────────────

test("les coordonnées de l'atelier sont présentes", function () {
    $response = $this->get(route('track', $this->ticket->tracking_token));

    $response->assertInertia(fn ($page) => $page
        ->where('ticket.shop.name', $this->shop->name)
    );
});
