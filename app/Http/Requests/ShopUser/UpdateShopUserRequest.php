<?php

namespace App\Http\Requests\ShopUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShopUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($this->route('user')),
            ],
            'role' => ['sometimes', Rule::in(['admin', 'technicien', 'caissiere','gestionnaire'])],
            'is_active' => ['sometimes', 'boolean'],
            'depot_ids' => ['nullable', 'array'],
            'depot_ids.*' => ['exists:depots,id'],
        ];
    }
}
