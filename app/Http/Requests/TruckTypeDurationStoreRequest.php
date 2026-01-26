<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TruckTypeDurationStoreRequest extends FormRequest
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
            'truck_type' => [
                'required',
                'string',
                'max:100',
                'unique:truck_type_durations,truck_type'
            ],
            'target_duration_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:1440' // Max 24 hours
            ]
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'truck_type.required' => 'Truck type is required.',
            'truck_type.unique' => 'Truck type already exists.',
            'truck_type.max' => 'Truck type maximum 100 characters.',
            'target_duration_minutes.required' => 'Target duration is required.',
            'target_duration_minutes.integer' => 'Target duration must be a number.',
            'target_duration_minutes.min' => 'Target duration minimum 0 minutes.',
            'target_duration_minutes.max' => 'Target duration maximum 1440 minutes (24 hours).'
        ];
    }
}
