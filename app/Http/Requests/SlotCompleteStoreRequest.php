<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlotCompleteStoreRequest extends FormRequest
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
            'actual_finish' => [
                'required',
                'date',
                'before_or_equal:now'
            ],
            'sj_start_number' => [
                'nullable',
                'string',
                'max:50'
            ],
            'sj_complete_number' => [
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
            'actual_finish.required' => 'Actual finish time is required.',
            'actual_finish.date' => 'Actual finish time must be a valid date.',
            'actual_finish.before_or_equal' => 'Actual finish time cannot be after current time.',
            'sj_start_number.max' => 'SJ start number maximum 50 characters.',
            'sj_complete_number.max' => 'SJ complete number maximum 50 characters.'
        ];
    }
}
