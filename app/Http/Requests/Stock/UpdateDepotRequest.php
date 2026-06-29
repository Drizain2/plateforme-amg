<?php

namespace App\Http\Requests\Stock;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDepotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.sometimes' => "Le nom n'est pas requis",
            'address.sometimes' => "L'address n'est pas requise",
            'phone.sometimes' => "Le telephone n'est pas requis",
            'is_active.sometimes' => "L'etat est requis",
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'address' => 'Adresse',
            'phone' => 'Telephone',
        ];
    }
}
