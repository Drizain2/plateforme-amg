<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorController extends Controller
{
    // ── Gestion du 2FA (depuis les paramètres) ───────────────────────────

    /** Active le 2FA et stocke le secret chiffré. */
    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $enable($request->user());

        return back()->with('success', 'Double authentification activée. Scannez le QR code pour configurer votre application.');
    }

    /** Confirme le 2FA en vérifiant le premier code TOTP. */
    public function confirm(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        /** @var User $user */
        $user = $request->user();

        if (! $provider->verify(decrypt($user->two_factor_secret), $request->code)) {
            return back()->withErrors(['code' => 'Code invalide. Vérifiez l\'heure de votre application d\'authentification.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return back()->with('success', '2FA confirmé et activé avec succès.');
    }

    /** Désactive le 2FA. */
    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $disable($request->user());

        return back()->with('success', 'Double authentification désactivée.');
    }

    /** Retourne le QR code SVG du secret 2FA. */
    public function qrCode(Request $request): \Illuminate\Http\Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_if(! $user->two_factor_secret, 404);

        return response($user->twoFactorQrCodeSvg(), 200, ['Content-Type' => 'image/svg+xml']);
    }

    /** Retourne les codes de récupération. */
    public function recoveryCodes(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($user->recoveryCodes());
    }

    /** Régénère les codes de récupération. */
    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        $generate($request->user());

        return back()->with('success', 'Nouveaux codes de récupération générés.');
    }

    // ── Challenge 2FA (depuis la page de connexion) ──────────────────────

    /** Affiche la page de vérification du code 2FA. */
    public function challengeCreate(): Response|RedirectResponse
    {
        if (! session()->has('login.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /** Vérifie le code 2FA ou le code de récupération saisi. */
    public function challenge(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        if (! session()->has('login.id')) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::find(session('login.id'));

        if (! $user) {
            session()->forget('login.id');

            return redirect()->route('login');
        }

        $code = $request->input('code');
        $recoveryCode = $request->input('recovery_code');

        $valid = false;

        if ($code) {
            $valid = $provider->verify(decrypt($user->two_factor_secret), $code);
        } elseif ($recoveryCode) {
            $codes = $user->recoveryCodes();
            if (in_array($recoveryCode, $codes, true)) {
                // Invalide le code utilisé
                $user->replaceRecoveryCode($recoveryCode);
                $valid = true;
            }
        }

        if (! $valid) {
            return back()->withErrors(['code' => 'Code invalide ou expiré.']);
        }

        session()->forget('login.id');
        Auth::loginUsingId($user->id, session()->pull('login.remember', false));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
