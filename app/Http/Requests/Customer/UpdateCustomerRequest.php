<?php

namespace App\Http\Requests\Customer;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'    => ['sometimes', 'string', 'max:255'],
            'email'   => [
                'nullable', 'email',
                Rule::unique('customers')->where(fn($q) =>
                    $q->where('shop_id', app('current_shop')->id)
                )->ignore($this->route('customer')),
            ],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes'   => ['nullable', 'string'],
        ];
    }
}
