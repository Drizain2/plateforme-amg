<?php

namespace App\Http\Requests\ShopUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'technicien','caissiere','gestionnaire'])],
            'depot_ids' => ['nullable', 'array'],
            'depot_ids.*' => ['exists:depots,id'],
        ];
    }
}
