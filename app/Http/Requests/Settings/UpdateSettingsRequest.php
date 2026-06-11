<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->hasRole('admin'); }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required', 'email',
                Rule::unique('shops')->ignore(app('current_shop')->id),
            ],
            'phone'    => ['nullable', 'string', 'max:20'],
            'address'  => ['nullable', 'string', 'max:500'],
            'logo'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
