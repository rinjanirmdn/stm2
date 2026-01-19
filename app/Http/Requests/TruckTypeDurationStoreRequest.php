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
            'truck_type.required' => 'Tipe truck wajib diisi.',
            'truck_type.unique' => 'Tipe truck sudah ada.',
            'truck_type.max' => 'Tipe truck maksimal 100 karakter.',
            'target_duration_minutes.required' => 'Durasi target wajib diisi.',
            'target_duration_minutes.integer' => 'Durasi target harus berupa angka.',
            'target_duration_minutes.min' => 'Durasi target minimal 0 menit.',
            'target_duration_minutes.max' => 'Durasi target maksimal 1440 menit (24 jam).'
        ];
    }
}
