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
        $shop = $request->user()?->shop;
        if (!$shop) abort(403, 'Vous devez avoir un shop pour accéder à cette page');
        // Injecter le shop_id dans tous les models via GlobalScope
        app()->instance('current_shop', $shop);
        return $next($request);
    }
}
