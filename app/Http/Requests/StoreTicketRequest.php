<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
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
             'customer_id'    => ['nullable', 'exists:customers,id'],
            'customer_name'  => ['required_without:customer_id', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'string', 'max:20'],

            // Appareil
            'device_id'      => ['nullable', 'exists:devices,id'],
            'device_type'    => ['required_without:device_id', 'string', 'max:100'],
            'device_brand'   => ['required_without:device_id', 'string', 'max:100'],
            'device_model'   => ['required_without:device_id', 'string', 'max:100'],
            'device_serial'  => ['nullable', 'string', 'max:100'],
            'device_color'   => ['nullable', 'string', 'max:50'],
            'condition_in'   => ['nullable', 'string'],

            // Ticket
            'depot_id'              => ['required', 'exists:depots,id'],
            'technician_id'         => ['nullable', 'exists:users,id'],
            'priority'              => ['required', Rule::enum(TicketPriority::class)],
            'description'           => ['required', 'string'],
            'estimated_return_date' => ['nullable', 'date', 'after:today'],
        ];
    }
}
