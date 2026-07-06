<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    $plan = Plan::factory()->create(['price' => 15000]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    User::factory()->admin()->create(['shop_id' => $shop->id]);

    // Un super_admin est nécessaire pour que handleWebhook() puisse valider le paiement.
    $superAdmin = User::factory()->create(['shop_id' => null, 'depot_active_id' => null]);
    $superAdmin->assignRole('super_admin');

    $this->payment = Payment::factory()->pending()->create([
        'shop_id' => $shop->id,
        'plan_id' => $plan->id,
        'reference' => 'PAY-2024-00001',
        'amount' => 15000,
        'gateway' => 'paydunya',
    ]);
});

// ── PayDunya ──────────────────────────────────────────────────────────────────

test('un webhook PayDunya completed valide le paiement', function () {
    $masterKey = 'test_master_key';
    Config::set('payment.paydunya.master_key', $masterKey);

    $token = 'checkout_token_abc';
    $hash = hash_hmac('sha256', $token, $masterKey);

    $this->postJson(route('webhooks.handle', ['gateway' => 'paydunya']), [
        'data' => [
            'hash' => $hash,
            'status' => 'completed',
            'invoice' => [
                'token' => $token,
                'custom_data' => ['payment_reference' => 'PAY-2024-00001'],
            ],
        ],
    ])->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Validated);
});

test('un webhook PayDunya cancelled rejette le paiement', function () {
    $masterKey = 'test_master_key';
    Config::set('payment.paydunya.master_key', $masterKey);

    $token = 'checkout_token_cancelled';
    $hash = hash_hmac('sha256', $token, $masterKey);

    $this->postJson(route('webhooks.handle', ['gateway' => 'paydunya']), [
        'data' => [
            'hash' => $hash,
            'status' => 'cancelled',
            'invoice' => [
                'token' => $token,
                'custom_data' => ['payment_reference' => 'PAY-2024-00001'],
            ],
        ],
    ])->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Rejected);
});

test('un webhook PayDunya avec signature invalide retourne 401', function () {
    Config::set('payment.paydunya.master_key', 'correct_key');

    $this->postJson(route('webhooks.handle', ['gateway' => 'paydunya']), [
        'data' => [
            'hash' => 'wrong_hash',
            'status' => 'completed',
            'invoice' => [
                'token' => 'some_token',
                'custom_data' => ['payment_reference' => 'PAY-2024-00001'],
            ],
        ],
    ])->assertUnauthorized();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

// ── Wave ──────────────────────────────────────────────────────────────────────

test('un webhook Wave succeeded valide le paiement', function () {
    $secret = 'test_wave_secret';
    Config::set('payment.wave.webhook_secret', $secret);

    $this->payment->update(['gateway' => 'wave']);

    $body = json_encode([
        'id' => 'cos_abc123',
        'client_reference' => 'PAY-2024-00001',
        'payment_status' => 'succeeded',
        'amount' => '15000',
        'currency' => 'XOF',
    ]);

    $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

    $this->withHeaders(['X-Wave-Signature' => $signature])
        ->postJson(route('webhooks.handle', ['gateway' => 'wave']), json_decode($body, true))
        ->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Validated);
});

test('un webhook Wave errored rejette le paiement', function () {
    $secret = 'test_wave_secret';
    Config::set('payment.wave.webhook_secret', $secret);

    $this->payment->update(['gateway' => 'wave']);

    $body = json_encode([
        'id' => 'cos_abc123',
        'client_reference' => 'PAY-2024-00001',
        'payment_status' => 'errored',
        'amount' => '15000',
        'currency' => 'XOF',
    ]);

    $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

    $this->withHeaders(['X-Wave-Signature' => $signature])
        ->postJson(route('webhooks.handle', ['gateway' => 'wave']), json_decode($body, true))
        ->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Rejected);
});

test('un webhook Wave avec signature invalide retourne 401', function () {
    Config::set('payment.wave.webhook_secret', 'correct_secret');

    $this->withHeaders(['X-Wave-Signature' => 'sha256=wrong_signature'])
        ->postJson(route('webhooks.handle', ['gateway' => 'wave']), [
            'id' => 'cos_abc123',
            'client_reference' => 'PAY-2024-00001',
            'payment_status' => 'succeeded',
        ])->assertUnauthorized();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('un webhook Wave sans en-tête de signature retourne 401', function () {
    Config::set('payment.wave.webhook_secret', 'correct_secret');

    $this->postJson(route('webhooks.handle', ['gateway' => 'wave']), [
        'id' => 'cos_abc123',
        'client_reference' => 'PAY-2024-00001',
        'payment_status' => 'succeeded',
    ])->assertUnauthorized();
});

// ── Passerelle inconnue ────────────────────────────────────────────────────────

test('un webhook vers une passerelle inconnue retourne 404', function () {
    $this->postJson(route('webhooks.handle', ['gateway' => 'unknown']))->assertNotFound();
});
