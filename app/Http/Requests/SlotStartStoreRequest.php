<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlotStartStoreRequest extends FormRequest
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
            'actual_start' => [
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
            'actual_start.required' => 'Waktu mulai aktual wajib diisi.',
            'actual_start.date' => 'Waktu mulai aktual harus berupa tanggal yang valid.',
            'actual_start.before_or_equal' => 'Waktu mulai aktual tidak boleh melebihi waktu sekarang.',
            'vehicle_number_snap.max' => 'Vehicle number maksimal 20 karakter.',
            'driver_number.max' => 'Driver number maksimal 50 karakter.'
        ];
    }
}
