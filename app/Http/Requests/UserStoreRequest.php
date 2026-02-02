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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'name' => [
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
                // 'exists:vendors,vendor_code', // Validasi ke tabel vendors (Tabel vendors tidak ada)
                Rule::requiredIf(fn () => (string) $this->input('role') === 'vendor'),
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required.',
            'name.max' => 'Full name maximum 255 characters.',
            'email.required' => 'Email is required.',
            'email.unique' => 'Email already exists.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password minimum 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Role must be selected.',
            'role.in' => 'Role must be admin, section_head, operator, or vendor.',
            'vendor_code.required' => 'Vendor Code (SAP) is required for vendor role.',
            'vendor_code.max' => 'Vendor Code (SAP) maximum 20 characters.',
            'vendor_code.exists' => 'Vendor Code not found in master data.',
        ];
    }
}
