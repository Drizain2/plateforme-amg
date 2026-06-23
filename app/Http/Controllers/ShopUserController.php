<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopUser\StoreShopUserRequest;
use App\Http\Requests\ShopUser\UpdateShopUserRequest;
use App\Http\Resources\ShopUserResource;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ShopUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('perm:users.view')->only(['index']);
        $this->middleware('perm:users.manage')->only(['store', 'update', 'destroy', 'toggleActive', 'resetPassword']);
    }

    public function index(): Response
    {
        $shop = app('current_shop');

        $users = User::where('shop_id', $shop->id)
            ->with(['roles', 'depots:id,name'])
            ->withCount(['tickets', 'assignedTickets'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Index', [
            'users' => ShopUserResource::collection($users)->resolve(),
            'depots' => Depot::select('id', 'name')->where('is_active', true)->get(),
            'userLimit' => $shop->plan?->max_users,
            'canAddUser' => $shop->canAddUser(),
        ]);
    }

    public function store(StoreShopUserRequest $request): RedirectResponse
    {
        $shop = app('current_shop');

        if (! $shop->canAddUser()) {
            return back()->with('error', "Limite d'utilisateurs atteinte pour l'offre {$shop->plan?->name} ({$shop->plan?->max_users}). Passez à une offre supérieure pour en ajouter.");
        }

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'shop_id' => app('current_shop')->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Str::random(16), // reset forcé à la première connexion
            ]);

            $user->assignRole($request->role);

            if ($request->depot_ids) {
                $user->depots()->sync($request->depot_ids);
            }

            return $user;
        });

        // Envoyer un email d'invitation avec lien reset password
        $token = Password::createToken($user);
        $user->sendPasswordResetNotification($token);

        return back()->with('success', "{$user->name} a été invité.");
    }

    public function update(UpdateShopUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        DB::transaction(function () use ($request, $user) {
            $user->update($request->safe()->except(['role', 'depot_ids']));

            if ($request->has('role')) {
                $user->syncRoles([$request->role]);
            }

            if ($request->has('depot_ids')) {
                $user->depots()->sync($request->depot_ids ?? []);
            }
        });

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        // Ne pas supprimer le dernier admin
        if ($user->hasRole('admin')) {
            $adminCount = User::where('shop_id', app('current_shop')->id)
                ->role('admin')
                ->count();

            if ($adminCount <= 1) {
                return back()->with('error', 'Impossible de supprimer le dernier administrateur.');
            }
        }

        // Soft disable plutôt que delete si tickets assignés
        if ($user->tickets()->exists()) {
            $user->update(['is_active' => false]);

            return back()->with('success', 'Compte désactivé.');
        }

        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        // Protéger le dernier admin actif
        if ($user->hasRole('admin') && $user->is_active) {
            $activeAdmins = User::where('shop_id', app('current_shop')->id)
                ->role('admin')
                ->where('is_active', true)
                ->count();

            if ($activeAdmins <= 1) {
                return back()->with('error', 'Impossible de désactiver le dernier administrateur actif.');
            }
        }

        $user->update(['is_active' => ! $user->is_active]);

        $label = $user->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Compte {$label}.");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        $token = Password::createToken($user);
        $user->sendPasswordResetNotification($token);

        return back()->with('success', "Email de réinitialisation envoyé à {$user->email}.");
    }

    private function authorizeUser(User $user): void
    {
        if ($user->shop_id !== app('current_shop')->id) {
            abort(403);
        }

        // Ne pas modifier soi-même via ce controller
        if ($user->id === auth()->id()) {
            abort(403, 'Utilisez les paramètres de profil pour modifier votre propre compte.');
        }
    }
}
