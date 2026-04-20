<?php

namespace App\Imports;

use App\Services\SlotService;
use DateTime;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class OfflineTxImport implements ToCollection, WithStartRow
{
    private $successCount = 0;
    private $errorCount = 0;
    private $errors = [];

    public function startRow(): int
    {
        return 3; // Skip row 2 (example data), data starts from row 3
    }

    private function normalizeKey($key)
    {
        // Strip asterisk and extra whitespace, then convert to snake_case
        $cleaned = trim(str_replace('*', '', (string) $key));
        return strtolower(str_replace(' ', '_', $cleaned));
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getErrorCount()
    {
        return $this->errorCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function collection(Collection $rows)
    {
        $warehouses = DB::table('md_warehouse')->pluck('id', 'wh_code')->toArray();
        $gates = DB::table('md_gates')->pluck('id', 'gate_number')->toArray(); // this is rough, since gates are linked to warehouse. We'll do it safely below.

        $allGates = DB::table('md_gates')->select('id', 'gate_number', 'warehouse_id')->get();

        $truckTargets = DB::table('md_truck')
            ->whereNotNull('truck_type')
            ->pluck('target_duration_minutes', 'truck_type')
            ->toArray();

        // lowercase all keys for safer matching
        $truckTargetDurations = [];
        foreach ($truckTargets as $tType => $tDur) {
            $truckTargetDurations[strtolower(trim($tType))] = $tDur;
        }

        foreach ($rows as $index => $row) {
            // Normalize keys since we're not using WithHeadingRow anymore
            $normalizedRow = [];
            foreach ($row as $key => $value) {
                $normalizedRow[$this->normalizeKey($key)] = $value;
            }

            // Check if row is empty
            if (! isset($normalizedRow['slot_type']) && ! isset($normalizedRow['po_number'])) {
                continue;
            }

            try {
                $whCode = isset($normalizedRow['warehouse_code']) ? trim($normalizedRow['warehouse_code']) : null;
                $whId = null;
                if ($whCode && isset($warehouses[strtoupper($whCode)])) {
                    $whId = $warehouses[strtoupper($whCode)];
                }

                $gateId = null;
                $gateNumber = isset($normalizedRow['gate_number']) ? trim($normalizedRow['gate_number']) : null;
                if ($gateNumber && $whId) {
                    foreach ($allGates as $g) {
                        if (strtoupper($g->gate_number) == strtoupper($gateNumber) && $g->warehouse_id == $whId) {
                            $gateId = $g->id;
                            break;
                        }
                    }
                }

                if (! $whId || ! $gateId) {
                    $this->errorCount++;
                    $errorMsg = 'Row '.($index + 1).': Warehouse or Gate not found';
                    if (! $whId) {
                        $errorMsg .= ' (warehouse_code: '.($whCode ?? 'empty').')';
                    }
                    if (! $gateId) {
                        $errorMsg .= ' (gate_number: '.($gateNumber ?? 'empty').')';
                    }
                    $this->errors[] = $errorMsg;
                    Log::warning('Offline Import: Warehouse/Gate not found for row '.$index, $normalizedRow);
                    continue; // Skip this row
                }

                $arrivalStr = $this->parseDate($normalizedRow['arrival_time'] ?? null);
                $startStr = $this->parseDate($normalizedRow['start_time'] ?? null);
                $finishStr = $this->parseDate($normalizedRow['finish_time'] ?? null);

                $duration = 0;
                $leadTime = 0;

                if ($startStr && $finishStr) {
                    $duration = round((strtotime($finishStr) - strtotime($startStr)) / 60);
                }

                if ($arrivalStr && $startStr) {
                    $leadTime = round((strtotime($startStr) - strtotime($arrivalStr)) / 60);
                }

                $slotType = strtolower(trim($normalizedRow['slot_type'] ?? 'unplanned'));
                $direction = strtolower(trim($normalizedRow['direction'] ?? 'inbound'));
                $truckType = trim($normalizedRow['truck_type'] ?? '');

                $targetDuration = null;
                if ($truckType && isset($truckTargetDurations[strtolower(trim($truckType))])) {
                    $targetDuration = $truckTargetDurations[strtolower(trim($truckType))];
                }

                $slotId = DB::table('slots')->insertGetId([
                    'warehouse_id' => $whId,
                    'planned_gate_id' => $gateId,
                    'actual_gate_id' => $gateId,
                    'arrival_time' => $arrivalStr,
                    'planned_start' => $startStr ?: ($arrivalStr ?: now()),
                    'planned_duration' => $targetDuration > 0 ? (int) $targetDuration : 0,
                    'actual_start' => $startStr,
                    'actual_finish' => $finishStr,
                    'status' => 'completed',
                    'vendor_name' => substr($normalizedRow['vendor_name'] ?? '-', 0, 100),
                    'vehicle_number_snap' => substr($normalizedRow['vehicle_number'] ?? '-', 0, 50),
                    'po_number' => substr($normalizedRow['po_number'] ?? '-', 0, 50),
                    'driver_name' => substr($normalizedRow['driver_name'] ?? '-', 0, 100),
                    'driver_number' => substr($normalizedRow['driver_number'] ?? '-', 0, 50),
                    'mat_doc' => substr($normalizedRow['sj_number'] ?? '', 0, 50),
                    'truck_type' => $truckType ?: null,
                    'slot_type' => $slotType === 'planned' ? 'planned' : 'unplanned',
                    'direction' => $direction === 'outbound' ? 'outbound' : 'inbound',
                    'target_duration_minutes' => $targetDuration,
                    'actual_duration_minutes' => $duration >= 0 ? (int) $duration : null,
                    'lead_time_minutes' => $leadTime >= 0 ? (int) $leadTime : null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'approval_notes' => 'Imported via Offline Excel. '.($normalizedRow['notes'] ?? ''),
                ]);
                $this->successCount++;
            } catch (Exception $e) {
                $this->errorCount++;
                $this->errors[] = 'Row '.($index + 1).': '.$e->getMessage();
                Log::error('Offline Import: Failed to process row '.$index.' - '.$e->getMessage());
            }
        }
    }

    private function parseDate($str)
    {
        if (! $str) {
            return;
        }
        try {
            // Maatwebsite Excel might parse date to integer timestamp or carbon instance
            if (is_numeric($str)) {
                return Date::excelToDateTimeObject($str)->format('Y-m-d H:i:s');
            }

            // Convert slashes to dashes: 15/04/2026 -> 15-04-2026 to parse DD-MM-YYYY natively in PHP
            $str = str_replace('/', '-', $str);

            $dt = new DateTime($str);

            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return;
        }
    }
}
