<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreSupplierRequest;
use App\Http\Requests\Stock\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $suppliers = Supplier::withCount('parts')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Stock/Suppliers/Index', [
            'suppliers' => SupplierResource::collection($suppliers),
            'filters' => request()->only(['search']),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse|JsonResponse
    {
        $supplier = Supplier::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json(new SupplierResource($supplier));
        }

        return back()->with('success', 'Fournisseur ajouté.');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return back()->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->parts()->exists()) {
            $supplier->update(['is_active' => false]);

            return back()->with('success', 'Fournisseur désactivé.');
        }

        $supplier->delete();

        return back()->with('success', 'Fournisseur supprimé.');
    }
}
