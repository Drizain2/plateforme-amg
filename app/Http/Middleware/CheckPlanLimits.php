<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    /**
     * @param  string  $check  'users' | 'depots' | 'tickets' | 'module:<nom>'
     */
    public function handle(Request $request, Closure $next, string $check): Response
    {
        if (! app()->has('current_shop')) {
            return $next($request);
        }

        $shop = app('current_shop');
        $plan = $shop->plan;

        if (! $plan) {
            return $next($request);
        }

        $error = match (true) {
            $check === 'users' => $this->checkUsers($shop, $plan),
            $check === 'depots' => $this->checkDepots($shop, $plan),
            $check === 'tickets' => $this->checkTickets($shop, $plan),
            str_starts_with($check, 'module:') => $this->checkModule($plan, substr($check, 7)),
            default => null,
        };

        if ($error === null) {
            return $next($request);
        }

        if ($request->header('X-Inertia')) {
            session()->flash('error', $error);

            return Inertia::location(route('settings.edit').'?tab=plan');
        }

        return Redirect::back()->with('error', $error);
    }

     private function checkUsers($shop, $plan): ?string
    {
        if ($plan->max_users !== null && $shop->users()->count() >= $plan->max_users) {
            return "Limite de {$plan->max_users} utilisateurs atteinte pour l'offre {$plan->name}. Passez à une offre supérieure pour en ajouter.";
        }

        return null;
    }

    private function checkDepots($shop, $plan): ?string
    {
        if ($plan->max_depots !== null && $shop->depots()->count() >= $plan->max_depots) {
            return "Limite de {$plan->max_depots} dépôt(s) atteinte pour l'offre {$plan->name}. Passez à une offre supérieure pour en ajouter.";
        }

        return null;
    }

    private function checkTickets($shop, $plan): ?string
    {
        if (in_array('tickets', $plan->disabled_modules ?? [], true)) {
            return "Le module tickets n'est pas disponible dans l'offre {$plan->name}.";
        }

        if ($plan->max_tickets !== null) {
            $count = $shop->tickets()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            if ($count >= $plan->max_tickets) {
                return "Limite de {$plan->max_tickets} tickets/mois atteinte pour l'offre {$plan->name}.";
            }
        }

        return null;
    }

    private function checkModule($plan, string $module): ?string
    {
        if (in_array($module, $plan->disabled_modules ?? [], true)) {
            return "Le module {$module} n'est pas disponible dans l'offre {$plan->name}.";
        }

        return null;
    }
}
