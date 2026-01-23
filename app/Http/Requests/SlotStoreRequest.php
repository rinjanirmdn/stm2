<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SlotStoreRequest extends FormRequest
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
            'po_number' => [
                'required',
                'string',
                'max:12'
            ],
            'truck_number' => [
                'nullable',
                'string',
                'max:50'
            ],
            'mat_doc' => [
                'nullable',
                'string',
                'max:50'
            ],
            'truck_type' => [
                'nullable',
                'string',
                'max:100'
            ],
            'vehicle_number_snap' => [
                'nullable',
                'string',
                'max:20'
            ],
            'driver_number' => [
                'nullable',
                'string',
                'max:50'
            ],
            'direction' => [
                'required',
                'string',
                'in:inbound,outbound'
            ],
            'warehouse_id' => [
                'required',
                'integer',
                'exists:warehouses,id'
            ],
            'planned_gate_id' => [
                'nullable',
                'integer',
                'exists:gates,id'
            ],
            'planned_start' => [
                'required',
                'date',
                'after:now'
            ],
            'planned_duration' => [
                'required',
                'integer',
                'min:15',
                'max:1440'
            ],
            'slot_type' => [
                'nullable',
                'string',
                'in:planned,unplanned'
            ]
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'po_number.exists' => 'PO number tidak ditemukan.',
            'po_number.max' => 'PO number maksimal 50 karakter.',
            'truck_number.max' => 'Truck number maksimal 50 karakter.',
            'mat_doc.max' => 'Material document maksimal 50 karakter.',
            'truck_type.max' => 'Truck type maksimal 100 karakter.',
            'vehicle_number_snap.max' => 'Vehicle number maksimal 20 karakter.',
            'driver_number.max' => 'Driver number maksimal 50 karakter.',
            'direction.required' => 'Direction wajib dipilih.',
            'direction.in' => 'Direction harus inbound atau outbound.',
            'warehouse_id.required' => 'Warehouse wajib dipilih.',
            'warehouse_id.exists' => 'Warehouse tidak ditemukan.',
            'planned_gate_id.exists' => 'Gate tidak ditemukan.',
            'planned_start.required' => 'Planned start wajib diisi.',
            'planned_start.date' => 'Planned start harus berupa tanggal yang valid.',
            'planned_start.after' => 'Planned start harus setelah waktu sekarang.',
            'planned_duration.required' => 'Planned duration wajib diisi.',
            'planned_duration.integer' => 'Planned duration harus berupa angka.',
            'planned_duration.min' => 'Planned duration minimal 15 menit.',
            'planned_duration.max' => 'Planned duration maksimal 1440 menit (24 jam).',
            'slot_type.in' => 'Slot type harus planned atau unplanned.'
        ];
    }
}
