<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockCountResource;
use App\Models\StockCount;
use App\Services\StockCountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockCountController extends Controller
{
    public function __construct(private StockCountService $stockCountService)
    {
        $this->middleware('perm:stock.view')->only(['index', 'show']);
        $this->middleware('perm:stock.count')->only(['store', 'update', 'validateCount']);
    }

    public function index(): Response
    {
        $counts = StockCount::with(['depot', 'user'])
            ->withCount('lines')
            ->latest()
            ->paginate(20);

        return Inertia::render('Stock/Counts/Index', [
            'counts' => StockCountResource::collection($counts),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(app()->has('current_depot'), 422, 'Aucun dépôt actif sélectionné.');

        $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $stockCount = $this->stockCountService->start(
            app('current_depot'),
            $request->user(),
            $request->input('note'),
        );

        return redirect()
            ->route('stock.counts.show', $stockCount->id)
            ->with('success', "Inventaire {$stockCount->number} démarré.");
    }

    public function show(StockCount $stockCount): Response
    {
        return Inertia::render('Stock/Counts/Show', [
            'stockCount' => (new StockCountResource(
                $stockCount->load(['depot', 'user', 'lines.stockDepot.part'])
            ))->resolve(),
        ]);
    }

    public function update(StockCount $stockCount, Request $request): RedirectResponse
    {
        $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.counted_quantity' => ['nullable', 'integer', 'min:0'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->stockCountService->saveCounts($stockCount, $request->input('lines'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Comptage enregistré.');
    }

    public function validateCount(StockCount $stockCount, Request $request): RedirectResponse
    {
        try {
            $this->stockCountService->validate($stockCount, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Inventaire validé, le stock a été ajusté.');
    }
}
