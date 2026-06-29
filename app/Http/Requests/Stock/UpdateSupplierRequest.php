<?php

namespace App\Http\Requests\Stock;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.sometimes' => "Le nom n'est pas requis",
            'email.sometimes' => "L'email n'est pas requis",
            'phone.sometimes' => "Le telephone n'est pas requis",
            'address.sometimes' => "L'adresse n'est pas requise",
            'is_active.sometimes' => "L'etat est requis",
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'email' => 'Email',
            'phone' => 'Telephone',
            'address' => 'Adresse',
        ];
    }
}
