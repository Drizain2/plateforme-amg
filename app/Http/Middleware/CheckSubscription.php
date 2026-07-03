<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Routes exemptées du contrôle d'abonnement.
     * On laisse toujours passer : paramètres (pour payer/changer de plan),
     * logout, webhooks, pricing public, et les routes admin plateforme.
     *
     * @var array<int, string>
     */
    private const EXEMPT_ROUTES = [
        'settings.*',
        'subscription.*',
        'webhooks.*',
        'pricing',
        'logout',
        'login',
        'register',
        'password.*',
        'verification.*',
        'admin.*',
        'depot.select',
        'depot.save',
        'depot.switch',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Pas d'atelier en session → laisser passer (EnsureTenantScope gère ça)
        if (! app()->has('current_shop')) {
            return $next($request);
        }

        // Routes exemptées
        if ($request->routeIs(...self::EXEMPT_ROUTES)) {
            return $next($request);
        }

        $shop = app('current_shop');

        // Plan gratuit (price = 0) → toujours accessible
        if ($shop->plan && $shop->plan->price === 0) {
            return $next($request);
        }

        // Période d'essai active → accessible
        if ($shop->isOnTrial()) {
            return $next($request);
        }

        // Abonnement actif → accessible
        if ($shop->hasActiveSubscription()) {
            return $next($request);
        }

        // Accès bloqué → redirection Inertia vers paramètres/abonnement
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('settings.edit').'?tab=plan');
        }

        return redirect()->route('settings.edit', ['tab' => 'plan'])
            ->with('error', 'Votre abonnement a expiré. Renouvelez-le pour continuer.');
    }
}
