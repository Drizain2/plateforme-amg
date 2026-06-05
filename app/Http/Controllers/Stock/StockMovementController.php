<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreMovementRequest;
use App\Http\Requests\Stock\TransferStockRequest;
use App\Http\Resources\PartResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Depot;
use App\Models\Part;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $filters = $request->only(['part_id', 'depot_id', 'type', 'from', 'to', 'shop_id']);
        $movements = StockMovement::with(['part:id,name,', 'depot:id,name', 'user:id,name', 'transferDepot:id,name'])
            ->when($filters['part_id'], function ($query) use ($filters) {
                $query->where('part_id', $filters['part_id']);
            })
            ->when($filters['depot_id'], function ($query) use ($filters) {
                $query->where('depot_id', $filters['depot_id']);
            })
            ->when($filters['type'], function ($query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->when($filters['from'], function ($query) use ($filters) {
                $query->where('created_at', '>=', $filters['from']);
            })
            ->when($filters['to'], function ($query) use ($filters) {
                $query->where('created_at', '<=', $filters['to']);
            })
            ->when($filters['shop_id'], function ($query) use ($filters) {
                $query->where('shop_id', $filters['shop_id']);
            })
            ->latest()
            ->paginate(20);

        return Inertia::render('Stock/Movement/Index', [
            'movements' => StockMovementResource::collection($movements),
            'depots' => Depot::select('id', 'name')->get(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreMovementRequest $request)
    {
        $part = Part::findOrFail($request->part_id);

        match ($request->type) {
            'in' => $this->stockService->restock($part, $request->quantity, $request->note ?? 'reapprovisionnement'),
            'out' => $this->stockService->consume($part, $request->quantity, $request->ticket_id, $request->user()),
            'adjustment' => $this->stockService->adjustment($part, $request->quantity, $request->note, $request->user()),
        };
        return back()->with('success', 'Mouvement enregistré.');
    }

    public function transfer(TransferStockRequest $request)
    {
        $part = Part::findOrFail($request->part_id);
        $targetDepot = Depot::findOrFail($request->target_depot_id);

        $this->stockService->transfer($part, $targetDepot, $request->quantity, $request->user());

        return back()->with('success', 'Transfert effectué avec succès');
    }

    public function alerts()
    {
        $alert = StockDepot::critical()
            ->with('depot:id,name', 'part:id,name')
            ->get();
        return Inertia::render('Stock/Alerts', ['alerts' => PartResource::collection(($alert))]);
    }
}
