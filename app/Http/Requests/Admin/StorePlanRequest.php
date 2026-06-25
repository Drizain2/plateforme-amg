<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Plan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(UserRole::SuperAdmin) && $this->user()->shop_id === null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('plans', 'slug')],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_depots' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'disabled_modules' => ['nullable', 'array'],
            'disabled_modules.*' => ['string', Rule::in(Plan::DISABLEABLE_MODULES)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'slug' => 'Slug',
            'price' => 'Prix',
            'max_users' => "Nombre d'utilisateurs max",
            'max_depots' => 'Nombre de dépôts max',
            'disabled_modules' => 'Modules désactivés',
        ];
    }
}
