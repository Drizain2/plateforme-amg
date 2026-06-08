<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
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
            abort(403, 'Vous devez être connecté pour accéder à cette page');
        }
        if (! $user->shop) {
            abort(403, 'Vous devez avoir un shop pour accéder à cette page');
        }
        if (! $user->shop->is_active) {
            abort(403, 'Votre shop est desactivé');
        }
        // Injecter le shop_id dans tous les models via GlobalScope
        app()->instance('current_shop', $user->shop);

        return $next($request);
    }
}
