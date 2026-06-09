<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'technician_id'         => ['nullable', 'exists:users,id'],
            'priority'              => ['sometimes', Rule::enum(TicketPriority::class)],
            'description'           => ['sometimes', 'string'],
            'diagnosis'             => ['nullable', 'string'],
            'estimated_price'       => ['nullable', 'numeric', 'min:0'],
            'estimated_return_date' => ['nullable', 'date'],
        ];
    }
}
