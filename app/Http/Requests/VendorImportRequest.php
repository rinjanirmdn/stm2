<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorImportRequest extends FormRequest
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
            'vendor_type' => [
                'required',
                'string',
                'in:supplier,customer'
            ],
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240' // Max 10MB
            ]
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'vendor_type.required' => 'Tipe vendor wajib dipilih.',
            'vendor_type.in' => 'Tipe vendor harus supplier atau customer.',
            'csv_file.required' => 'File CSV wajib diunggah.',
            'csv_file.file' => 'File harus berupa file yang valid.',
            'csv_file.mimes' => 'File harus berformat CSV atau TXT.',
            'csv_file.max' => 'File maksimal 10MB.'
        ];
    }
}
