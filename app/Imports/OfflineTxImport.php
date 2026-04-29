<?php

namespace App\Imports;

use DateTime;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class OfflineTxImport implements ToCollection, WithHeadingRow
{
    private $successCount = 0;

    private $errorCount = 0;

    private $errors = [];

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
            // Check if row is empty
            if (! isset($row['slot_type']) && ! isset($row['po_number'])) {
                continue;
            }

            // Skip example row if the user didn't delete it
            if (str_starts_with(strtolower(trim($row['slot_type'] ?? '')), 'example:')) {
                continue;
            }

            try {
                $whCode = isset($row['warehouse_code']) ? trim($row['warehouse_code']) : null;
                $whId = null;
                if ($whCode && isset($warehouses[strtoupper($whCode)])) {
                    $whId = $warehouses[strtoupper($whCode)];
                }

                $gateId = null;
                $gateNumber = isset($row['gate_number']) ? trim($row['gate_number']) : null;
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
                    $errorMsg = 'Row '.($index + 2).': Warehouse or Gate not found';
                    if (! $whId) {
                        $errorMsg .= ' (warehouse_code: '.($whCode ?? 'empty').')';
                    }
                    if (! $gateId) {
                        $errorMsg .= ' (gate_number: '.($gateNumber ?? 'empty').')';
                    }
                    $this->errors[] = $errorMsg;
                    Log::warning('Offline Import: Warehouse/Gate not found for row '.$index, $row->toArray());

                    continue; // Skip this row
                }

                $arrivalStr = $this->parseDate($row['arrival_time'] ?? null);
                $startStr = $this->parseDate($row['start_time'] ?? null);
                $finishStr = $this->parseDate($row['finish_time'] ?? null);

                $duration = 0;
                $leadTime = 0;

                if ($startStr && $finishStr) {
                    $duration = round((strtotime($finishStr) - strtotime($startStr)) / 60);
                }

                if ($arrivalStr && $startStr) {
                    $leadTime = round((strtotime($startStr) - strtotime($arrivalStr)) / 60);
                }

                $slotType = strtolower(trim($row['slot_type'] ?? 'unplanned'));
                $direction = strtolower(trim($row['direction'] ?? 'inbound'));
                $truckType = trim($row['truck_type'] ?? '');

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
                    'vendor_name' => substr($row['vendor_name'] ?? '-', 0, 100),
                    'vehicle_number_snap' => substr($row['vehicle_number'] ?? '-', 0, 50),
                    'po_number' => substr($row['po_number'] ?? '-', 0, 50),
                    'driver_name' => substr($row['driver_name'] ?? '-', 0, 100),
                    'driver_number' => substr($row['driver_number'] ?? '-', 0, 50),
                    'mat_doc' => substr($row['sj_number'] ?? '', 0, 50),
                    'truck_type' => $truckType ?: null,
                    'slot_type' => $slotType === 'planned' ? 'planned' : 'unplanned',
                    'direction' => $direction === 'outbound' ? 'outbound' : 'inbound',
                    'target_duration_minutes' => $targetDuration,
                    'actual_duration_minutes' => $duration >= 0 ? (int) $duration : null,
                    'lead_time_minutes' => $leadTime >= 0 ? (int) $leadTime : null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'approval_notes' => 'Imported via Offline Excel. '.($row['notes'] ?? ''),
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
