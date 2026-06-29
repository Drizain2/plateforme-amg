<?php

namespace App\Http\Requests\Stock;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('parts')->where(fn ($q) => $q->where('shop_id', app('current_shop')->id))],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_compat' => ['nullable', 'array'],
            'brand_compat.*' => ['string'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'sku' => 'SKU',
            'supplier_id' => 'Fournisseur',
            'category_id' => 'Catégorie',
            'unit_price' => "Prix d'achat",
            'sell_price' => 'Prix de vente',
        ];
    }
}
