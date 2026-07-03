<?php

use App\Models\Plan;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// ── Page de vérification ──────────────────────────────────────────────────────

test('un utilisateur non vérifié voit la page de vérification', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Auth/VerifyEmail'));
});

test('un utilisateur déjà vérifié est redirigé depuis la page de vérification', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard'));
});

// ── Vérification du lien ──────────────────────────────────────────────────────

test('un utilisateur peut vérifier son email via le lien signé', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('un lien de vérification invalide est rejeté', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.verify', [
            'id' => $user->id,
            'hash' => 'invalid-hash',
        ]))
        ->assertStatus(403);
});

// ── Renvoi de l'email ─────────────────────────────────────────────────────────

test("un utilisateur non vérifié peut renvoyer l'email de vérification", function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test("un utilisateur déjà vérifié est redirigé s'il tente de renvoyer", function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard'));

    Notification::assertNothingSent();
});

// ── Accès protégé par verified ────────────────────────────────────────────────

test('un utilisateur non vérifié est bloqué sur le dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('un utilisateur vérifié accède normalement au dashboard', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

// ── Inscription : événement Registered + notifications ───────────────────────

test("l'inscription envoie un email de bienvenue et redirige vers la vérification", function () {
    Notification::fake();
    Event::fake([Registered::class]);

    $plan = Plan::factory()->create(['price' => 0, 'is_active' => true]);

    $this->post(route('register'), [
        'shop_name' => 'Atelier Test',
        'name' => 'Jean Dupont',
        'email' => 'jean@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'plan_id' => $plan->id,
    ])->assertRedirect(route('verification.notice'));

    Event::assertDispatched(Registered::class);

    $user = User::where('email', 'jean@test.com')->firstOrFail();
    Notification::assertSentTo($user, WelcomeNotification::class);
});
