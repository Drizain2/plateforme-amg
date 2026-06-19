<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StorePartRequest;
use App\Http\Requests\Stock\UpdatePartRequest;
use App\Http\Resources\PartResource;
use App\Models\Categorie;
use App\Models\Part;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PartController extends Controller
{
    public function __construct()
    {
        $this->middleware('perm:stock.view')->only(['index', 'create', 'edit', 'search']);
        $this->middleware('perm:stock.create')->only(['store']);
        $this->middleware('perm:stock.edit')->only(['update']);
        $this->middleware('perm:stock.delete')->only(['destroy']);
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only(['search', 'category_id', 'critical']);

        $parts = Part::query()
            ->with(['category:id,name', 'supplier:id,name', 'stockDepots:id,part_id,depot_id,quantity,alert_quantity'])
            ->when($filters['search'] ?? null, fn ($q) => $q->where(
                fn ($q) => $q
                    ->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('sku', 'like', "%{$filters['search']}%")
            ))
            ->when($filters['category_id'] ?? null, fn ($q) => $q->where('category_id', $filters['category_id']))
            ->when($filters['critical'] ?? null, fn ($q) => $q->whereHas('stockDepots', fn ($q) => $q->whereColumn('quantity', '<=', 'alert_quantity')))
            ->whereHas('stockDepots')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Stock/Parts/Index', [
            'parts' => PartResource::collection($parts),
            'filters' => $filters,
            'categories' => Categorie::select('id', 'name')->where('is_active', true)->get(),
            'suppliers' => Supplier::select('id', 'name')->where('is_active', true)->get(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Stock/Parts/Create', [
            'categories' => Categorie::select('id', 'name')->where('is_active', true)->get(),
            'suppliers' => Supplier::select('id', 'name')->where('is_active', true)->get(),
        ]);
    }

    public function store(StorePartRequest $request)
    {
        Part::create($request->validated());

        return redirect()->route('stock.parts.index')->with('success', 'Pièce ajoutée.');
    }

    public function edit(Part $part): InertiaResponse
    {
        return Inertia::render('Stock/Parts/Edit', [
            'part' => (new PartResource($part->load(['category', 'supplier', 'stockDepots.depot'])))->resolve(),
            'categories' => Categorie::select('id', 'name')->where('is_active', true)->get(),
            'suppliers' => Supplier::select('id', 'name')->where('is_active', true)->get(),
        ]);
    }

    public function update(UpdatePartRequest $request, Part $part)
    {
        $part->update($request->validated());

        return back()->with('success', 'Pièce mise à jour.');
    }

    public function destroy(Part $part)
    {
        $hasMovements = $part->stockDepots()->whereHas('movements')->exists();

        if ($hasMovements) {
            $part->update(['is_active' => false]);

            return back()->with('success', 'Pièce désactivée (mouvements existants).');
        }

        $part->stockDepots()->delete();
        $part->delete();

        return back()->with('success', 'Pièce supprimée.');
    }

    public function search(): JsonResponse
    {
        $depotId = request('depot_id');

        $parts = Part::with(['stockDepots' => function ($q) use ($depotId) {
            $q->when($depotId, fn ($q) => $q->where('depot_id', $depotId)->where('quantity', '>', 0));
        }])
            ->where(fn ($q) => $q
                ->where('name', 'like', '%'.request('q').'%')
                ->orWhere('sku', 'like', '%'.request('q').'%'))
            ->where('is_active', true)
            ->when($depotId, fn ($q) => $q->whereHas('stockDepots', fn ($q) => $q->where('depot_id', $depotId)->where('quantity', '>', 0)))
            ->limit(8)
            ->get(['id', 'name', 'sku', 'unit_price', 'sell_price']);

        // Aplatir par dépôt : une entrée par (pièce × dépôt) pour que le frontend
        // reçoive bien depot_id et quantity. Quand un dépôt est demandé, on ne
        // garde que les pièces effectivement en stock dans ce dépôt.
        $results = $parts->flatMap(function (Part $part) use ($depotId) {
            if ($part->stockDepots->isEmpty()) {
                return $depotId ? [] : [[
                    'id' => $part->id,
                    'name' => $part->name,
                    'sku' => $part->sku,
                    'quantity' => 0,
                    'depot_id' => null,
                    'sell_price' => $part->sell_price,
                ]];
            }

            return $part->stockDepots->map(fn ($sd) => [
                'id' => $part->id,
                'name' => $part->name,
                'sku' => $part->sku,
                'quantity' => $sd->quantity,
                'depot_id' => $sd->depot_id,
                'sell_price' => $part->sell_price,
            ]);
        });

        return response()->json($results);
    }
}
