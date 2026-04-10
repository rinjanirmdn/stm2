<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nik' => is_string($this->nik) ? trim($this->nik) : $this->nik,
            'email' => is_string($this->email) ? trim($this->email) : $this->email,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'vendor_code' => is_string($this->vendor_code) ? trim($this->vendor_code) : $this->vendor_code,
            'role' => is_string($this->role) ? trim($this->role) : $this->role,
        ]);
    }

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
        $userId = $this->route('userId') ?? $this->route('user') ?? $this->route('id');
        if (is_object($userId) && isset($userId->id)) {
            $userId = $userId->id;
        }

        return [
            'nik' => [
                'required',
                'string',
                'max:50',
                Rule::unique('md_users', 'nik')->ignore($userId),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:50',
                Rule::unique('md_users', 'email')->ignore($userId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'password' => [
                'nullable',
                'string',
                Password::min(8)->letters()->numbers(),
                function ($attribute, $value, $fail) {
                    if ($value && ! preg_match('/^[A-Z]/', $value)) {
                        $fail('Password harus diawali dengan huruf kapital.');
                    }
                },
                'confirmed',
            ],
            'role' => [
                'required',
                'string',
                'in:admin,operator,section_head,vendor,security,super_account,display_account',
            ],
            'vendor_code' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return (string) $this->input('role') === 'vendor';
                }),
            ],
            'permissions' => [
                'nullable',
                'array',
            ],
            'permissions.*' => [
                'string',
                'max:255',
                'exists:md_permissions,perm_name',
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
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Role must be selected.',
            'role.in' => 'Role must be admin or operator.',
        ];
    }
}
