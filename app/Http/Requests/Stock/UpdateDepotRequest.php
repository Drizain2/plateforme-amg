<?php

namespace App\Http\Requests\Stock;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDepotRequest extends FormRequest
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
            'name' => ['sometimes', 'string'],
            'adresse' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.sometimes' => "Le nom n'est pas requis",
            'adresse.sometimes' => "L'adresse n'est pas requise",
            'phone.sometimes' => "Le telephone n'est pas requis",
            'is_active.sometimes' => "L'etat est requis",
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
