<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'min:8',
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
