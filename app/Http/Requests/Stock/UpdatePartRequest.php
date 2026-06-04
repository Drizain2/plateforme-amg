<?php

namespace App\Http\Requests\Stock;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasDepotAccess($this->route('part')->depot);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "suplier_id"=> ["nullable","exists:supliers,id"],
            "name" =>["sometimes","string","max:250"],
            "sku" =>["nullable","string","max:250"],
            "description" =>["nullable","string"],
            "category_id" =>["nullable","exists:categories,id"],
            "brand_compat"  => ['nullable', 'array'],
            'brand_compat.*'=> ['string'],
            "unit_price" => ["nullable", "decimal:2"],
            "sell_price" => ["nullable", "decimal:2"],
        ];
    }
}
