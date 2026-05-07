<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
                'unique:md_users,nik',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:50',
                'unique:md_users,email',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->mixedCase()->numbers(),
                'confirmed',
            ],
            'role' => [
                'required',
                'string',
                'in:admin,section_head,operator,admin_wh,vendor,security,super_account,display_account',
            ],
            'vendor_code' => [
                'nullable',
                'string',
                'max:20',
                Rule::requiredIf(function () {
                    return (string) $this->input('role') === 'vendor' && ! $this->input('is_internal_vendor');
                }),
            ],
            'is_internal_vendor' => [
                'nullable',
                'boolean',
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
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Role must be selected.',
            'role.in' => 'Invalid role selected.',
            'vendor_code.required' => 'Vendor Code (SAP) is required for vendor role.',
            'vendor_code.max' => 'Vendor Code (SAP) maximum 20 characters.',
            'vendor_code.exists' => 'Vendor Code not found in master data.',
        ];
    }
}
