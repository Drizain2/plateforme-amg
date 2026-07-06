<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct()
    {
        // Le profil personnel (nom, email, mot de passe) est accessible à
        // tout utilisateur authentifié. Seuls les paramètres de l'atelier
        // (boutique, abonnement) requièrent la permission settings.manage.
        $this->middleware('perm:settings.manage')->only(['updateShop', 'updatePlan']);
    }

    public function edit(): Response
    {
        $shop = app('current_shop')->load('plan');

        return Inertia::render('Settings/Index', [
            'shop' => [
                'name' => $shop->name,
                'email' => $shop->email,
                'phone' => $shop->phone,
                'address' => $shop->address,
                'logo_url' => $shop->logo_url,
                'plan' => $shop->plan,
                'tax_rate' => $shop->tax_rate ?? 20.00,
            ],
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'profile' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'twoFactor' => [
                'enabled' => (bool) auth()->user()->two_factor_secret,
                'confirmed' => (bool) auth()->user()->two_factor_confirmed_at,
            ],
        ]);
    }

    public function updateShop(UpdateSettingsRequest $request): RedirectResponse
    {
        $shop = app('current_shop');
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo
            if ($shop->logo_url) {
                Storage::disk('public')->delete(
                    str_replace('/storage/', '', $shop->logo_url)
                );
            }

            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_url'] = Storage::url($path);
        }

        $shop->update($data);

        return back()->with('success', 'Paramètres mis à jour.');
    }

    public function updatePlan(Plan $plan): RedirectResponse
    {
        $shop = app('current_shop');

        if (! $plan->is_active) {
            return back()->with('error', "L'offre {$plan->name} n'est plus disponible.");
        }

        if ($shop->plan_id === $plan->id) {
            return back();
        }

        if ($error = $shop->exceedsLimitsOf($plan)) {
            return back()->with('error', "{$error} Réduisez votre utilisation avant de changer d'offre.");
        }

        $shop->update(['plan_id' => $plan->id]);

        return back()->with('success', "Vous êtes maintenant sur l'offre {$plan->name}.");
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email',
                Rule::unique('users')->ignore(auth()->id()),
            ],
        ]);

        auth()->user()->update($request->only('name', 'email'));

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Mot de passe mis à jour.');
    }
}
