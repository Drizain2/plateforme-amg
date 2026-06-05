<?php

namespace App\Http\Controllers\Stock;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategorieController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Stock/Categories/Index', [
            'categories' => Categorie::orderBy('name')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        Categorie::create($request->only('name', 'is_active'));

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

            return back()->with('success', 'Catégorie désactivée (pièces existantes).');
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
