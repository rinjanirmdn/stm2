<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'po_number' => $this->po_number,
            'mat_doc' => $this->mat_doc,
            'truck_type' => $this->truck_type,
            'direction' => $this->direction,

            // Relations
            'warehouse' => [
                'id' => $this->warehouse_id,
                'name' => $this->warehouse_name,
                'code' => $this->warehouse_code,
            ],
            'vendor' => $this->vendor_name ? [
                'name' => $this->vendor_name,
            ] : null,
            'planned_gate' => $this->when($this->planned_gate_id, [
                'id' => $this->planned_gate_id,
                'number' => $this->planned_gate_number,
                'warehouse' => $this->when($this->planned_gate_warehouse_name, [
                    'name' => $this->planned_gate_warehouse_name,
                    'code' => $this->planned_gate_warehouse_code,
                ]),
            ]),
            'actual_gate' => $this->when($this->actual_gate_id, [
                'id' => $this->actual_gate_id,
                'number' => $this->actual_gate_number,
            ]),

            // Timing
            'planned' => [
                'start' => $this->planned_start,
                'duration' => $this->planned_duration,
            ],
            'actual' => [
                'arrival' => $this->arrival_time,
                'start' => $this->actual_start,
                'finish' => $this->actual_finish,
            ],
            'target_duration' => $this->planned_duration,

            // Status
            'status' => $this->status,
            'slot_type' => $this->slot_type,
            'is_late' => (bool) $this->is_late,
            'moved_gate' => (bool) $this->moved_gate,
            'blocking_risk' => $this->blocking_risk,

            // Additional info
            'cancelled_reason' => $this->cancelled_reason,
            'late_reason' => $this->late_reason,
            'vehicle_number_snap' => $this->vehicle_number_snap,
            'driver_number' => $this->driver_number,

            // Metadata
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
