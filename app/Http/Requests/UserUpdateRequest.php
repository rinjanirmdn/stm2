<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
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
        $userId = $this->route('userId');

        return [
            'nik' => [
                'required',
                'string',
                'max:50',
                Rule::unique('md_users', 'nik')->ignore($userId)
            ],
            'email' => [
                'required',
                'string',
                'max:50',
                Rule::unique('md_users', 'email')->ignore($userId)
            ],
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed'
            ],
            'role' => [
                'required',
                'string',
                'in:admin,operator,section_head,vendor'
            ],
            'vendor_code' => [
                'nullable',
                'string',
                'max:20',
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
            'password.min' => 'Password minimum 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Role must be selected.',
            'role.in' => 'Role must be admin or operator.',
        ];
    }
}
