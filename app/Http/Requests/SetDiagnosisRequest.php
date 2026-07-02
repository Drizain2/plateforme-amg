<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagnosis' => ['required', 'string', 'max:2000'],
            'estimated_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
