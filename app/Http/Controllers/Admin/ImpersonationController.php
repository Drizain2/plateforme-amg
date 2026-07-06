<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Connecte le super_admin en tant qu'admin de l'atelier donné.
     * L'ID original est conservé en session pour permettre le retour.
     */
    public function start(Shop $shop): RedirectResponse
    {
        $admin = $shop->admin;

        abort_if(! $admin, 404, 'Cet atelier n\'a pas d\'administrateur.');

        session(['impersonating_user_id' => auth()->id()]);

        Auth::loginUsingId($admin->id);

        return redirect()->route('dashboard')
            ->with('success', "Simulation activée : vous accédez en tant que {$admin->name} ({$shop->name}).");
    }

    /**
     * Restaure la session du super_admin original et retourne au dashboard admin.
     */
    public function stop(): RedirectResponse
    {
        $originalId = session('impersonating_user_id');

        abort_if(! $originalId, 403, 'Aucune simulation en cours.');

        session()->forget('impersonating_user_id');

        Auth::loginUsingId($originalId);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Simulation terminée.');
    }
}
