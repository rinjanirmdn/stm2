<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlotArrivalStoreRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'arrival_time' => [
                'required',
                'date',
                'before_or_equal:now'
            ],
            'vehicle_number_snap' => [
                'nullable',
                'string',
                'max:20'
            ],
            'driver_number' => [
                'nullable',
                'string',
                'max:50'
            ]
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'arrival_time.required' => 'Arrival time is required.',
            'arrival_time.date' => 'Arrival time must be a valid date.',
            'arrival_time.before_or_equal' => 'Arrival time cannot be after current time.',
            'vehicle_number_snap.max' => 'Vehicle number maximum 20 characters.',
            'driver_number.max' => 'Driver number maximum 50 characters.'
        ];
    }
}
