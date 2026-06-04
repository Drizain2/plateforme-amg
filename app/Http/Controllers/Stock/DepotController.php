<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreDepotRequest;
use App\Http\Requests\Stock\UpdateDepotRequest;
use App\Http\Resources\DepotResource;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $depots = Depot::withCount("stocks")
            ->with("users:id,name")
            ->get();

        return Inertia::render("Stock/Depots/Index", [
            "depots" => DepotResource::collection($depots),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDepotRequest $request)
    {
        $depot = Depot::create($request->validated());
        $depot->users()->attach($request->users);
        return Inertia::redirect()->route("stock.depots.index")->with("success", "Dépôt {$depot->name} enregistré");
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDepotRequest $request, Depot $depot)
    {
        $depot->update($request->validated());

        return back()->with('success', 'Dépôt mis à jour.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Depot $depot)
    {
        // Soft disable plutôt que suppression si des pièces existent
        if($depot->stock()->exists()){
            $depot->update(['is_active' => false]);

            return back()->with('success', 'Dépôt désactivé.');
        }

        $depot->delete();
        return back()->with("success","Dépôt supprimé.");
    }

    public function attachUser(Depot $depot,Request $request){
        $request->validate([
            "user_id"=>["required", "exists:users,id"]
        ]);
        
        $depot->users()->syncWithoutDetaching([$request->user_id]);
        return back()->with("success","Utilisateurs ajoutés.");
    }

    public function detachUser(Depot $depot, User $user): RedirectResponse
    {
        $depot->users()->detach($user);

        return back()->with('success', 'Technicien retiré du dépôt.');
    }
}
