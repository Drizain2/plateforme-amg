<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    //
    public function index()
    {
        $categories = Categorie::all();

        return view('stock.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('stock.categories.create');
    }

    public function store(Request $request)
    {
        $categorie = Categorie::create($request->all());

        return redirect()->route('categories.index');
    }

    public function edit(Categorie $categorie)
    {
        return view('stock.categories.edit', compact('categorie'));
    }

    public function update(Request $request, Categorie $categorie)
    {
        $categorie->update($request->all());

        return redirect()->route('categories.index');
    }

    public function destroy(Categorie $categorie)
    {
        $categorie->delete();

        return redirect()->route('categories.index');
    }
}
