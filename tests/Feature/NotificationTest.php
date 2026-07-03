<?php

use App\Models\Depot;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->admin = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    app()->instance('current_shop', $this->shop);
    app()->instance('current_depot', $this->depot);
});

// ── Liste ───────────────────────────────────────────────────────────────────

test('un utilisateur peut récupérer ses notifications', function () {
    DatabaseNotification::create([
        'id' => Str::uuid(),
        'type' => 'TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->admin->id,
        'data' => ['message' => 'Notification de test'],
        'read_at' => null,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('notifications.index'));

    $response->assertOk();
    $response->assertJsonStructure([
        'notifications' => [['id', 'data', 'read_at', 'created_at']],
        'unread_count',
    ]);
    $response->assertJsonPath('unread_count', 1);
});

test('les notifications des autres utilisateurs ne sont pas retournées', function () {
    $autre = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    DatabaseNotification::create([
        'id' => Str::uuid(),
        'type' => 'TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $autre->id,
        'data' => ['message' => 'Notif privée'],
        'read_at' => null,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('notifications.index'));

    $response->assertOk();
    $response->assertJsonPath('unread_count', 0);
    $response->assertJsonCount(0, 'notifications');
});

// ── Marquer comme lu ────────────────────────────────────────────────────────

test('un utilisateur peut marquer une notification comme lue', function () {
    $notif = DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->admin->id,
        'data' => ['message' => 'À lire'],
        'read_at' => null,
    ]);

    $response = $this->actingAs($this->admin)->postJson(route('notifications.read', $notif->id));

    $response->assertOk();
    $response->assertJson(['ok' => true]);
    expect($notif->fresh()->read_at)->not->toBeNull();
});

test("marquer la notification d'un autre utilisateur est silencieux (null-safe)", function () {
    $autre = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $notifAutre = DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $autre->id,
        'data' => ['message' => 'Privée'],
        'read_at' => null,
    ]);

    // Le ?-> dans le contrôleur retourne ['ok' => true] même si la notif n'appartient pas à l'user
    $response = $this->actingAs($this->admin)->postJson(route('notifications.read', $notifAutre->id));

    $response->assertOk();
    expect($notifAutre->fresh()->read_at)->toBeNull();
});

// ── Tout marquer comme lu ────────────────────────────────────────────────────

test('un utilisateur peut marquer toutes ses notifications comme lues', function () {
    foreach (range(1, 3) as $i) {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->admin->id,
            'data' => ['message' => "Notification $i"],
            'read_at' => null,
        ]);
    }

    $response = $this->actingAs($this->admin)->postJson(route('notifications.read-all'));

    $response->assertOk();
    $response->assertJson(['ok' => true]);
    expect($this->admin->unreadNotifications()->count())->toBe(0);
});
