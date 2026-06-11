<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\PermissionService;

class CheckPermission
{
    public function __construct(private PermissionService $permissionService) {}
    public function handle(Request $request, Closure $next, string $permission): Response
    {
       if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!$this->permissionService->has(auth()->user(), $permission)) {
            abort(403, "Permission requise : {$permission}");
        }

        return $next($request);
    }
}
