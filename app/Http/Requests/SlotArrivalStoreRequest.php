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
            'arrival_time.required' => 'Waktu kedatangan wajib diisi.',
            'arrival_time.date' => 'Waktu kedatangan harus berupa tanggal yang valid.',
            'arrival_time.before_or_equal' => 'Waktu kedatangan tidak boleh melebihi waktu sekarang.',
            'vehicle_number_snap.max' => 'Vehicle number maksimal 20 karakter.',
            'driver_number.max' => 'Driver number maksimal 50 karakter.'
        ];
    }
}
