<?php

namespace App\Http\Requests\Stock;

use App\Models\Depot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            "depot_id" => ["required", "exists:depots,id"],
            "suplier_id"=> ["nullable", "exists:supliers,id"],
            "name" =>["required","string","max:250"],
            "sku" => ["nullable","string","max:250",Rule::unique('parts')->where( fn($query)=>$query->where('depot_id',$this->depot_id))],
            "description" => ["nullable","string"],
            "category_id" =>["nullable","exists:categories,id"],
            "brand_compat"  => ['nullable', 'array'],
            'brand_compat.*'=> ['string'],
            "unit_price" => ["nullable", "decimal:2"],
            "sell_price" => ["nullable", "decimal:2"],
            "quantity" => ["nullable", "integer"],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est requis',
            'sku.required' => 'Le SKU est requis',
            'unit_price.required' => 'Le prix d\'achat est requis',
            'sell_price.required' => 'Le prix de vente est requis',
            'category_id.required' => 'La categorie est requise',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'sku' => 'SKU',
            'unit_price' => 'Prix d\'achat',
            'sell_price' => 'Prix de vente',
            'category_id' => 'Categorie',
        ];
    }
}
