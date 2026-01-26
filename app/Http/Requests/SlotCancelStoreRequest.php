<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlotCancelStoreRequest extends FormRequest
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
            'cancelled_reason' => [
                'nullable',
                'string',
                'max:500'
            ]
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'cancelled_reason.max' => 'Cancellation reason maximum 500 characters.'
        ];
    }
}
