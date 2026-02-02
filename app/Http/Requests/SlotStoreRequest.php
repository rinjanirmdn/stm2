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
                'exists:md_warehouse,id'
            ],
            'planned_gate_id' => [
                'nullable',
                'integer',
                'exists:md_gates,id'
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
            'po_number.exists' => 'PO number not found.',
            'po_number.max' => 'PO number maximum 50 characters.',
            'truck_number.max' => 'Truck number maximum 50 characters.',
            'mat_doc.max' => 'Material document maximum 50 characters.',
            'truck_type.max' => 'Truck type maximum 100 characters.',
            'vehicle_number_snap.max' => 'Vehicle number maximum 20 characters.',
            'driver_number.max' => 'Driver number maximum 50 characters.',
            'direction.required' => 'Direction must be selected.',
            'direction.in' => 'Direction must be inbound or outbound.',
            'warehouse_id.required' => 'Warehouse must be selected.',
            'warehouse_id.exists' => 'Warehouse not found.',
            'planned_gate_id.exists' => 'Gate not found.',
            'planned_start.required' => 'Planned start is required.',
            'planned_start.date' => 'Planned start must be a valid date.',
            'planned_start.after' => 'Planned start must be after current time.',
            'planned_duration.required' => 'Planned duration is required.',
            'planned_duration.integer' => 'Planned duration must be a number.',
            'planned_duration.min' => 'Planned duration minimum 15 minutes.',
            'planned_duration.max' => 'Planned duration maximum 1440 minutes (24 hours).',
            'slot_type.in' => 'Slot type must be planned or unplanned.'
        ];
    }
}
