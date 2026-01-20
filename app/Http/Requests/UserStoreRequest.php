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
            'nik.required' => 'NIK wajib diisi.',
            'nik.unique' => 'NIK sudah digunakan.',
            'nik.max' => 'NIK maksimal 50 karakter.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'full_name.max' => 'Nama lengkap maksimal 255 karakter.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role harus admin, section_head, operator, atau vendor.',
            'vendor_code.required' => 'Vendor Code (SAP) wajib diisi untuk role vendor.',
            'vendor_code.max' => 'Vendor Code (SAP) maksimal 20 karakter.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.'
        ];
    }
}
