<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request)
    {
        $user = $request->authenticate();

        // Redirection vers le challenge 2FA si l'utilisateur l'a activé et confirmé
        if ($user->hasEnabledTwoFactorAuthentication()) {
            Auth::logout();
            $request->session()->put('login.id', $user->getKey());
            $request->session()->put('login.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        if (! $user->shop_id) {
            return redirect()->intended(route('admin.plans.index'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->update(['depot_active_id' => null]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
