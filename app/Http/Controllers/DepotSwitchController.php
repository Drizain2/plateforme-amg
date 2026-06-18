<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DepotSwitchController extends Controller
{
    /**
     * Page de sélection de dépôt pour les non-admins (plusieurs dépôts assignés).
     */
    public function select(Request $request): Response|RedirectResponse
    {
        $depots = $request->user()->depots()->where('is_active', true)->get();

        if ($depots->count() <= 1) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/DepotSelect', [
            'depots' => $depots,
        ]);
    }

    /**
     * Enregistre le dépôt sélectionné pour les non-admins.
     */
    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'depot_id' => 'required|integer|exists:depots,id',
        ]);

        $user = $request->user();
        $depot = Depot::withoutGlobalScope('depot')->findOrFail($validated['depot_id']);

        if (! $user->hasDepotAccess($depot)) {
            abort(403, "Vous n'avez pas accès à ce dépôt");
        }

        $user->depot_active_id = $depot->id;
        $user->save();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Permet à l'admin/super_admin de changer de dépôt actif (ou de passer en vue globale).
     */
    public function switch(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdminOrSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'depot_id' => 'nullable|integer|exists:depots,id',
        ]);

        $depotId = $validated['depot_id'] ?? null;

        if ($depotId) {
            $depot = Depot::withoutGlobalScope('depot')->findOrFail($depotId);
            abort_unless($depot->shop_id === $user->shop_id, 403, "Ce dépôt n'appartient pas à votre shop");
        }

        $user->depot_active_id = $depotId;
        $user->save();

        return back();
    }
}
