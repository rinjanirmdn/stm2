<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
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
            'nik' => [
                'required',
                'string',
                'max:50',
                'unique:users,nik'
            ],
            'full_name' => [
                'required',
                'string',
                'max:255'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ],
            'role' => [
                'required',
                'string',
                'in:admin,section_head,operator,vendor'
            ],
            'vendor_code' => [
                'nullable',
                'string',
                'max:20',
                Rule::requiredIf(fn () => (string) $this->input('role') === 'vendor'),
            ],
            'is_active' => [
                'boolean'
            ]
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'nik.required' => 'NIK is required.',
            'nik.unique' => 'NIK already exists.',
            'nik.max' => 'NIK maximum 50 characters.',
            'full_name.required' => 'Full name is required.',
            'full_name.max' => 'Full name maximum 255 characters.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password minimum 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Role must be selected.',
            'role.in' => 'Role must be admin, section_head, operator, or vendor.',
            'vendor_code.required' => 'Vendor Code (SAP) is required for vendor role.',
            'vendor_code.max' => 'Vendor Code (SAP) maximum 20 characters.',
            'is_active.boolean' => 'Active status must be boolean.'
        ];
    }
}
