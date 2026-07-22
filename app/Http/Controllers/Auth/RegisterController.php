<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'plans' => Plan::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'description', 'price', 'features', 'disabled_modules']),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $shop = Shop::create([
                'name' => $data['shop_name'],
                'slug' => $this->uniqueSlug($data['shop_name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'plan_id' => $data['plan_id'],
                'trial_ends_at' => now()->addDays(14),
                'is_active' => true,
            ]);

            $user = User::create([
                'shop_id' => $shop->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),// pour le test vue qu'on a pas d'hebergement
                'is_active' => true,
            ]);

            $user->assignRole('admin');

            return $user;
        });

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $user->shop()->update(['logo_url' => Storage::url($path)]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        event(new Registered($user));

        $user->notify(new WelcomeNotification(
            shopName: $user->shop->name,
            trialEndsAt: $user->shop->trial_ends_at->translatedFormat('d F Y'),
        ));

        return redirect()->route('verification.notice');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Shop::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
