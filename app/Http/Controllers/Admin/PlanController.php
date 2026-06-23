<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Plans/Index', [
            'plans' => Plan::withCount('shops')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        Plan::create($request->validated());

        return back()->with('success', 'Offre créée.');
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return back()->with('success', 'Offre mise à jour.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->shops()->exists()) {
            $plan->update(['is_active' => false]);

            return back()->with('success', 'Offre désactivée (encore utilisée par des ateliers).');
        }

        $plan->delete();

        return back()->with('success', 'Offre supprimée.');
    }
}
