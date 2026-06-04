<?php

namespace App\Http\Requests\Stock;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(UserRole::Admin) || $this->user()->hasRole(UserRole::SuperAdmin);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'adresse' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est requis',
            'adresse.required' => "L'adresse est requise",
            'phone.required' => 'Le telephone est requis',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'adresse' => 'Adresse',
            'phone' => 'Telephone',
        ];
    }
}
