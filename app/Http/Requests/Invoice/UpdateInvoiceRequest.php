<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
