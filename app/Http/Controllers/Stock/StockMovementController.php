<?php

namespace App\Http\Controllers\Stock;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreMovementRequest;
use App\Http\Requests\Stock\TransferStockRequest;
use App\Http\Resources\StockDepotResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Depot;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Services\PermissionService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function __construct(private StockService $stockService)
    {
        $this->middleware('perm:stock.view')->only(['index', 'alerts']);
        $this->middleware('perm:stock.transfer')->only(['transfer']);
        // store : permission vérifiée inline selon le type de mouvement
    }

    public function index(Request $request)
    {
        $filters = $request->only(['depot_id', 'type', 'from', 'part_id', 'to']);

        $movements = StockMovement::with(['stock.part:id,name,sku', 'depot:id,name', 'user:id,name', 'transferDepot:id,name', 'ticket:id,reference'])
            ->when($filters['depot_id'] ?? null, fn ($q) => $q->where('depot_id', $filters['depot_id']))
            ->when($filters['part_id'] ?? null, fn ($q) => $q->whereHas('stock', fn ($q) => $q->where('part_id', $filters['part_id'])))
            ->when($filters['type'] ?? null, fn ($q) => $q->where('type', $filters['type']))
            ->when($filters['from'] ?? null, fn ($q) => $q->where('created_at', '>=', $filters['from']))
            ->when($filters['to'] ?? null, fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Résumé période
        $summary = [
            'total_in' => StockMovement::whereIn('type', ['in', 'transfert'])
                ->when($filters['depot_id'] ?? null, fn ($q, $v) => $q->where('depot_id', $v))
                ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
                ->sum('quantity'),
            'total_out' => StockMovement::whereIn('type', ['out', 'transfer_out'])
                ->when($filters['depot_id'] ?? null, fn ($q, $v) => $q->where('depot_id', $v))
                ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
                ->sum('quantity'),
            'total_adjustments' => StockMovement::where('type', 'adjustment')
                ->when($filters['depot_id'] ?? null, fn ($q, $v) => $q->where('depot_id', $v))
                ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
                ->count(),
        ];

        return Inertia::render('Stock/Movement/Index', [
            'movements' => StockMovementResource::collection($movements),
            'depots' => Depot::select('id', 'name')->where('is_active', true)->get(),
            'filters' => $filters,
            'summary' => $summary,
            'types' => array_map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ], StockMovementType::cases()),
        ]);
    }

    public function store(StoreMovementRequest $request)
    {
        $permissionMap = [
            'in' => 'stock.restock',
            'out' => 'stock.restock',
            'adjustment' => 'stock.adjust',
        ];

        $required = $permissionMap[$request->type] ?? 'stock.restock';

        abort_unless(app(PermissionService::class)->has($request->user(), $required), 403, "Permission requise : {$required}");

        $stock = StockDepot::findOrFail($request->stock_id);

        try {
            match ($request->type) {
                'in' => $this->stockService->restock($stock, $request->quantity, $request->user(), $request->note ?? 'réapprovisionnement'),
                'out' => $this->stockService->consume($stock, $request->quantity, $request->ticket_id, $request->user()),
                'adjustment' => $this->stockService->adjustment($stock, $request->quantity, $request->note ?? '', $request->user()),
            };
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Mouvement enregistré.');
    }

    public function transfer(TransferStockRequest $request)
    {
        $source = StockDepot::findOrFail($request->stock_id);
        $targetDepot = Depot::findOrFail($request->to_depot_id);

        try {
            $this->stockService->transfer($source, $targetDepot, $request->quantity, $request->user());
        } catch (InsufficientStockException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfert effectué avec succès.');
    }

    public function alerts()
    {
        $alerts = StockDepot::critique()
            ->with(['depot:id,name', 'part:id,name,sku'])
            ->get();

        return Inertia::render('Stock/Alerts', [
            'alerts' => StockDepotResource::collection($alerts)->resolve(),
        ]);
    }
}
