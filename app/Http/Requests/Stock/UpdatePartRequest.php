<?php

namespace App\Http\Requests\Stock;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(UserRole::Admin) || $this->user()->hasRole(UserRole::SuperAdmin);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $partId = $this->route('part')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('parts')->ignore($partId)->where(fn ($q) => $q->where('shop_id', app('current_shop')->id))],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_compat' => ['nullable', 'array'],
            'brand_compat.*' => ['string'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
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
