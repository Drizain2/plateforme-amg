<?php

namespace App\Http\Controllers\Stock;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategorieController extends Controller
{
    public function __construct()
    {
        $this->middleware('perm:stock.view')->only(['index']);
        $this->middleware('perm:stock.create')->only(['store']);
        $this->middleware('perm:stock.edit')->only(['update']);
        $this->middleware('perm:stock.delete')->only(['destroy']);
    }

    public function index(): Response
    {
        return Inertia::render('Stock/Categories/Index', [
            'categories' => Categorie::orderBy('name')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $categorie = Categorie::create($request->only('name', 'is_active'));

        if ($request->wantsJson()) {
            return response()->json($categorie);
        }

        return back()->with('success', 'Catégorie ajoutée.');
    }

    public function update(Request $request, Categorie $categorie): RedirectResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $categorie->update($request->only('name', 'is_active'));

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Categorie $categorie): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($categorie->parts()->exists()) {
            $categorie->update(['is_active' => false]);

            return back()->with('success', 'Catégorie désactivée (articles existants).');
        }

        $categorie->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(
            request()->user()->hasRole(UserRole::Admin) || request()->user()->hasRole(UserRole::SuperAdmin),
            403
        );
    }
}
