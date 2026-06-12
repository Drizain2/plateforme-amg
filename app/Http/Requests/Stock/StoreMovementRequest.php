<?php

namespace App\Http\Requests\Stock;

use App\Models\Depot;
use App\Models\StockDepot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    protected function prepareForValidation(): void
    {
        // Résoudre stock_id à partir de part_id + depot_id
        if ($this->part_id && $this->depot_id) {
            $stock = StockDepot::firstOrCreate(
                ['part_id' => $this->part_id, 'depot_id' => $this->depot_id],
                ['quantity' => 0, 'alert_quantity' => 0]
            );

            $this->merge(['stock_id' => $stock->id]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->hasDepotAccess(Depot::findOrFail($this->depot_id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'part_id' => ['required', 'exists:parts,id'],
            'depot_id' => ['required', 'exists:depots,id'],
            'stock_id' => ['required', 'exists:stock_depots,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', Rule::in(['in', 'out', 'adjustment'])],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'stock_id.required' => 'Le stock est requis',
            'stock_id.exists' => "Le stock n'existe pas",
            'quantity.required' => 'La quantite est requise',
            'quantity.integer' => 'La quantite doit etre un entier',
            'type.required' => 'Le type est requis',
            'type.in' => 'Le type doit etre stock_in ou stock_out',
        ];
    }

    public function attributes(): array
    {
        return [
            'stock_id' => 'Stock',
            'quantity' => 'Quantite',
            'type' => 'Type',
        ];
    }
}
