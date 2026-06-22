<?php

use App\Models\Depot;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create(['name' => 'Atelier Test']);
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->admin = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    $this->admin->assignRole('admin');
});

test('inviter un utilisateur envoie le mail personnalisé de définition de mot de passe', function () {
    Notification::fake();

    $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Nouveau Technicien',
        'email' => 'nouveau@example.com',
        'role' => 'technicien',
    ]);

    $newUser = User::where('email', 'nouveau@example.com')->first();

    Notification::assertSentTo($newUser, ResetPasswordNotification::class);
});

test('réinitialiser le mot de passe d\'un utilisateur envoie le mail personnalisé', function () {
    Notification::fake();

    $technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $this->actingAs($this->admin)->post(route('users.reset-password', $technicien->id));

    Notification::assertSentTo(
        $technicien,
        ResetPasswordNotification::class,
        function (ResetPasswordNotification $notification) use ($technicien) {
            $mail = $notification->toMail($technicien);

            return str_contains($mail->subject, 'Atelier Test');
        }
    );
});
