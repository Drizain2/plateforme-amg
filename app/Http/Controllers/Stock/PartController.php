<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StorePartRequest;
use App\Http\Requests\Stock\UpdatePartRequest;
use App\Http\Resources\PartResource;
use App\Models\Categorie;
use App\Models\Depot;
use App\Models\Part;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'depot_id', 'critical']);
        $parts = Part::query()
            ->with('category', 'supplier:id,name', 'stock_depots:id,part_id,depot_id,quantity,threshold')
            ->when($filters['search'], function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('sku', 'like', "%{$filters['search']}%");
            })
            ->when($filters['category_id'], fn($q) => $q->where('category_id', $filters['category_id']))
            ->when($filters['depot_id'], fn($q) => $q->whereHas('stock_depots', fn($q) => $q->where('depot_id', $filters['depot_id'])))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render("Stock/Parts/Index", [
            "parts" => PartResource::collection($parts),
            "filters" => $filters,
            "depots" => Depot::select("id", "name")->where("is_active", true)->get(),
            "categories" => Categorie::select("id", "name")->where("is_active", true)->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render("Stock/Parts/Create", [
            'depots' => Depot::select('id', 'name')->where('is_active', true)->get(),
            'suppliers' => Supplier::select('id', 'name')->where('is_active', true)->get(),

        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePartRequest $request)
    {
        Part::create($request->validated());
        return redirect()
            ->route('stock.parts.index')
            ->with('success', 'Pièce ajoutée.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Part $part)
    {
        return Inertia::render("Stock/Parts/Edit", [
            'part' => new PartResource($part->load(['depot', 'supplier'])),
            'depots'    => Depot::select('id', 'name')->where('is_active', true)->get(),
            'suppliers' => Supplier::select('id', 'name')->where('is_active', true)->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePartRequest $request, Part $part)
    {
        $part->update($request->validated());
        return back()->with('success', 'Pièce mise à jour.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Part $part)
    {
        // Soft disable si mouvements existants
        if ($part->movements()->exists()) {
            $part->update(['is_active' => false]);
            return back()->with('success', 'Pièce désactivée.');
        }

        $part->delete();
        return back()->with('success', 'Pièce supprimée.');
    }
}
