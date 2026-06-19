<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatus;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PermissionService;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchaseService)
    {
        $this->middleware('perm:purchases.view')->only(['index', 'show']);
        $this->middleware('perm:purchases.create')->only(['create', 'store']);
        // transition : vérifié inline selon le statut cible
    }

    public function index(): Response
    {
        $filters = request()->only(['search', 'status']);

        $purchases = Purchase::with(['supplier', 'depot'])
            ->when(
                $filters['search'] ?? null,
                fn ($q, $s) => $q->where('number', 'like', "%$s%")
                    ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%$s%"))
            )
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Purchases/Index', [
            'purchases' => PurchaseResource::collection($purchases),
            'filters' => $filters,
            'statuses' => array_map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ], PurchaseStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Purchases/Create', [
            'suppliers' => Supplier::select('id', 'name')->where('is_active', true)->get(),
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        abort_unless(app()->has('current_depot'), 422, 'Aucun dépôt actif sélectionné.');

        $purchase = $this->purchaseService->create($request->validated());

        return redirect()
            ->route('purchases.show', $purchase->id)
            ->with('success', "Achat {$purchase->number} créé.");
    }

    public function show(Purchase $purchase): Response
    {
        return Inertia::render('Purchases/Show', [
            'purchase' => (new PurchaseResource(
                $purchase->load(['supplier', 'depot', 'lines'])
            ))->resolve(),
        ]);
    }

    public function transition(Purchase $purchase, Request $request): RedirectResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(PurchaseStatus::class)],
        ]);

        $newStatus = PurchaseStatus::from($request->status);

        $permission = match ($newStatus) {
            PurchaseStatus::Received => 'purchases.receive',
            PurchaseStatus::Paid => 'purchases.mark_paid',
            default => 'purchases.create',
        };
        abort_unless(app(PermissionService::class)->has($request->user(), $permission), 403, "Permission requise : {$permission}");

        try {
            $this->purchaseService->transition($purchase, $newStatus, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Statut mis à jour.');
    }
}
