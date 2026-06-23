<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Vous devez être connecté pour accéder à cette page.');
        }

        if ($user->shop_id !== null || ! $user->hasRole('super_admin')) {
            abort(403, 'Accès réservé aux opérateurs de la plateforme.');
        }

        return $next($request);
    }
}
