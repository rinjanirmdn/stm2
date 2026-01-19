<?php

namespace App\DTOs;

use Carbon\Carbon;

class SlotDTO
{
    public ?int $id = null;
    public ?string $ticketNumber = null;
    public ?string $poNumber = null;
    public ?string $matDoc = null;
    public ?string $sjNumber = null;
    public ?string $truckNumber = null;
    public ?string $truckType = null;
    public ?string $direction = null;
    public ?int $poId = null;
    public ?int $warehouseId = null;
    public ?int $vendorId = null;
    public ?int $plannedGateId = null;
    public ?int $actualGateId = null;
    public ?string $status = null;
    public ?string $slotType = null;
    public ?Carbon $plannedStart = null;
    public ?Carbon $plannedFinish = null;
    public ?Carbon $actualArrival = null;
    public ?Carbon $actualStart = null;
    public ?Carbon $actualFinish = null;
    public ?int $targetDurationMinutes = null;
    public ?int $actualDurationMinutes = null;
    public ?int $plannedDuration = null;
    public ?int $leadTimeMinutes = null;
    public ?bool $isLate = false;
    public ?bool $movedGate = false;
    public ?float $blockingRisk = null;
    public ?string $notes = null;
    public ?string $cancelReason = null;
    public ?string $lateReason = null;
    public ?string $vehicleNumberSnap = null;
    public ?string $driverNumber = null;
    public ?int $createdBy = null;
    public ?Carbon $createdAt = null;
    public ?Carbon $updatedAt = null;

    // Related data
    public ?string $warehouseName = null;
    public ?string $warehouseCode = null;
    public ?string $vendorName = null;
    public ?int $plannedGateNumber = null;
    public ?int $actualGateNumber = null;

    public function __construct(array $data = [])
    {
        $this->fillFromArray($data);
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function fillFromArray(array $data): void
    {
        foreach ($data as $key => $value) {
            $property = $this->snakeToCamel($key);
            if (property_exists($this, $property)) {
                $this->$property = $this->castValue($key, $value);
            }
        }
    }

    public function toArray(): array
    {
        $data = [];
        foreach (get_object_vars($this) as $key => $value) {
            $snakeKey = $this->camelToSnake($key);
            if ($value instanceof Carbon) {
                $data[$snakeKey] = $value->format('Y-m-d H:i:s');
            } else {
                $data[$snakeKey] = $value;
            }
        }
        return $data;
    }

    public function toDatabaseArray(): array
    {
        return [
            'ticket_number' => $this->ticketNumber,
            'po_number' => $this->poNumber,
            'mat_doc' => $this->matDoc,
            'sj_number' => $this->sjNumber,
            'truck_number' => $this->truckNumber,
            'truck_type' => $this->truckType,
            'direction' => $this->direction,
            'po_id' => $this->poId,
            'warehouse_id' => $this->warehouseId,
            'bp_id' => $this->vendorId,
            'planned_gate_id' => $this->plannedGateId,
            'actual_gate_id' => $this->actualGateId,
            'status' => $this->status,
            'slot_type' => $this->slotType,
            'planned_start' => $this->plannedStart?->format('Y-m-d H:i:s'),
            'planned_finish' => $this->plannedFinish?->format('Y-m-d H:i:s'),
            'arrival_time' => $this->actualArrival?->format('Y-m-d H:i:s'),
            'actual_start' => $this->actualStart?->format('Y-m-d H:i:s'),
            'actual_finish' => $this->actualFinish?->format('Y-m-d H:i:s'),
            'target_duration_minutes' => $this->targetDurationMinutes,
            'actual_duration_minutes' => $this->actualDurationMinutes,
            'planned_duration' => $this->plannedDuration,
            'lead_time_minutes' => $this->leadTimeMinutes,
            'is_late' => $this->isLate,
            'moved_gate' => $this->movedGate,
            'blocking_risk' => $this->blockingRisk,
            'notes' => $this->notes,
            'cancel_reason' => $this->cancelReason,
            'late_reason' => $this->lateReason,
            'vehicle_number_snap' => $this->vehicleNumberSnap,
            'driver_number' => $this->driverNumber,
            'created_by' => $this->createdBy,
        ];
    }

    private function snakeToCamel(string $key): string
    {
        return lcfirst(str_replace('_', '', ucwords($key, '_')));
    }

    private function camelToSnake(string $key): string
    {
        return strtolower(preg_replace('/([A-Z])/', '_$1', $key));
    }

    private function castValue(string $key, $value)
    {
        // Handle date fields
        $dateFields = [
            'planned_start', 'planned_finish', 'arrival_time',
            'actual_start', 'actual_finish', 'created_at', 'updated_at'
        ];

        if (in_array($key, $dateFields) && $value) {
            return $value instanceof Carbon ? $value : new Carbon($value);
        }

        // Handle boolean fields
        $booleanFields = ['is_late', 'moved_gate'];
        if (in_array($key, $booleanFields)) {
            return (bool) $value;
        }

        // Handle float fields
        if ($key === 'blocking_risk') {
            return $value ? (float) $value : null;
        }

        return $value;
    }

    // Computed properties
    public function getIsPlanned(): bool
    {
        return $this->slotType !== 'unplanned';
    }

    public function getIsUnplanned(): bool
    {
        return $this->slotType === 'unplanned';
    }

    public function getIsCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function getIsArrived(): bool
    {
        return in_array($this->status, ['arrived', 'waiting', 'in_progress']);
    }

    public function getIsPending(): bool
    {
        return in_array($this->status, ['scheduled', 'arrived', 'waiting']);
    }

    public function getDurationDisplay(): string
    {
        if ($this->actualDurationMinutes) {
            return $this->actualDurationMinutes . ' min';
        }
        if ($this->targetDurationMinutes) {
            return $this->targetDurationMinutes . ' min (target)';
        }
        return '-';
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'scheduled' => 'blue',
            'arrived' => 'yellow',
            'waiting' => 'orange',
            'in_progress' => 'green',
            'completed' => 'gray',
            'cancelled' => 'red',
            default => 'gray'
        };
    }

    public function getGateDisplay(): string
    {
        if ($this->actualGateNumber) {
            return 'Gate ' . $this->actualGateNumber;
        }
        if ($this->plannedGateNumber) {
            return 'Gate ' . $this->plannedGateNumber . ' (planned)';
        }
        return '-';
    }

    public function getWarehouseDisplay(): string
    {
        return $this->warehouseName ?? $this->warehouseCode ?? '-';
    }
}
