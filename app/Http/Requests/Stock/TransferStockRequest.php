<?php

namespace App\Http\Requests\Stock;

use App\Models\Depot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasDepotAccess(Depot::findOrFail($this->from_depot_id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "stock_id" => ["required", "exists:stock_depots,id"],
            "from_depot_id" => ["required", "exists:depots,id"],
            "to_depot_id" => ["required", "exists:depots,id",Rule::notIn([$this->from_depot_id])],
            "quantity" => ["required", "integer"],
            "note"=>["nullable",'string']
        ];
    }

    public function messages(): array
    {
        return [
            "part_id.required" => "La piece est requise",
            "part_id.exists" => "La piece n'existe pas",
            "from_depot_id.required" => "Le depot de depart est requis",
            "from_depot_id.exists" => "Le depot de depart n'existe pas",
            "to_depot_id.required" => "Le depot d'arrivee est requis",
            "to_depot_id.exists" => "Le depot d'arrivee n'existe pas",
            "quantity.required" => "La quantite est requise",
            "quantity.integer" => "La quantite doit etre un entier",
        ];
    }

    public function attributes(): array
    {
        return [
            "part_id" => "Piece",
            "from_depot_id" => "Depot de depart",
            "to_depot_id" => "Depot d'arrivee",
            "quantity" => "Quantite",
        ];
    }
}
