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

    /**
     * Column mapping: heading key => [label, Excel column letter]
     */
    private const COLUMN_MAP = [
        'slot_type' => ['Slot Type', 'A'],
        'direction' => ['Direction', 'B'],
        'truck_type' => ['Truck Type', 'C'],
        'arrival_time' => ['Arrival Time', 'D'],
        'start_time' => ['Start Time', 'E'],
        'finish_time' => ['Finish Time', 'F'],
        'po_number' => ['PO/SO Number', 'G'],
        'sj_number' => ['SJ Number', 'H'],
        'vehicle_number' => ['Vehicle Number', 'I'],
        'driver_name' => ['Driver Name', 'J'],
        'driver_number' => ['Driver Number', 'K'],
        'vendor_name' => ['Vendor Name', 'L'],
        'warehouse_code' => ['Warehouse Code', 'M'],
        'gate_number' => ['Gate Number', 'N'],
        'notes' => ['Notes', 'O'],
    ];

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
        $allGates = DB::table('md_gates')->select('id', 'gate_number', 'warehouse_id')->get();

        $truckTargets = DB::table('md_truck')
            ->whereNotNull('truck_type')
            ->pluck('target_duration_minutes', 'truck_type')
            ->toArray();

        // lowercase all keys for safer matching
        $truckTargetDurations = [];
        $validTruckTypes = [];
        foreach ($truckTargets as $tType => $tDur) {
            $truckTargetDurations[strtolower(trim($tType))] = $tDur;
            $validTruckTypes[] = trim($tType);
        }

        $validWhCodes = array_keys($warehouses);
        $validGateNumbers = $allGates->pluck('gate_number')->unique()->values()->toArray();

        $slotService = app(\App\Services\SlotService::class);
        $dateAddExpr = $slotService->getDateAddExpression('planned_start', 'planned_duration');

        $validRowsToInsert = [];
        $seenSjs = [];
        $seenSlots = [];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2; // heading is row 1, data starts row 2

            // Check if row is empty
            if (! isset($row['slot_type']) && ! isset($row['po_number'])) {
                continue;
            }

            // Skip example row if the user didn't delete it
            if (str_starts_with(strtolower(trim($row['slot_type'] ?? '')), 'example:')) {
                continue;
            }

            $rowErrors = [];

            // --- Validate required fields ---
            $slotTypeRaw = trim($row['slot_type'] ?? '');
            if ($slotTypeRaw === '') {
                $rowErrors[] = $this->formatError($excelRow, 'slot_type', $slotTypeRaw, 'Required. Options: planned, unplanned');
            } elseif (! in_array(strtolower($slotTypeRaw), ['planned', 'unplanned'])) {
                $rowErrors[] = $this->formatError($excelRow, 'slot_type', $slotTypeRaw, 'Invalid value. Options: planned, unplanned');
            }

            $directionRaw = trim($row['direction'] ?? '');
            if ($directionRaw === '') {
                $rowErrors[] = $this->formatError($excelRow, 'direction', $directionRaw, 'Required. Options: inbound, outbound');
            } elseif (! in_array(strtolower($directionRaw), ['inbound', 'outbound'])) {
                $rowErrors[] = $this->formatError($excelRow, 'direction', $directionRaw, 'Invalid value. Options: inbound, outbound');
            }

            $truckType = trim($row['truck_type'] ?? '');
            if ($truckType === '') {
                $rowErrors[] = $this->formatError($excelRow, 'truck_type', $truckType, 'Required. Options: '.implode(', ', $validTruckTypes));
            } elseif (! isset($truckTargetDurations[strtolower($truckType)])) {
                $rowErrors[] = $this->formatError($excelRow, 'truck_type', $truckType, 'Truck type not found in master data. Options: '.implode(', ', $validTruckTypes));
            }

            $arrivalRaw = $row['arrival_time'] ?? null;
            $arrivalStr = $this->parseDate($arrivalRaw);
            if (! $arrivalStr && $arrivalRaw !== null && trim((string) $arrivalRaw) !== '') {
                $rowErrors[] = $this->formatError($excelRow, 'arrival_time', (string) $arrivalRaw, 'Invalid date format. Expected: DD-MM-YYYY HH:mm');
            } elseif (! $arrivalStr) {
                $rowErrors[] = $this->formatError($excelRow, 'arrival_time', '', 'Required. Format: DD-MM-YYYY HH:mm');
            }

            $startRaw = $row['start_time'] ?? null;
            $startStr = $this->parseDate($startRaw);
            if (! $startStr && $startRaw !== null && trim((string) $startRaw) !== '') {
                $rowErrors[] = $this->formatError($excelRow, 'start_time', (string) $startRaw, 'Invalid date format. Expected: DD-MM-YYYY HH:mm');
            } elseif (! $startStr) {
                $rowErrors[] = $this->formatError($excelRow, 'start_time', '', 'Required. Format: DD-MM-YYYY HH:mm');
            }

            $finishRaw = $row['finish_time'] ?? null;
            $finishStr = $this->parseDate($finishRaw);
            if (! $finishStr && $finishRaw !== null && trim((string) $finishRaw) !== '') {
                $rowErrors[] = $this->formatError($excelRow, 'finish_time', (string) $finishRaw, 'Invalid date format. Expected: DD-MM-YYYY HH:mm');
            } elseif (! $finishStr) {
                $rowErrors[] = $this->formatError($excelRow, 'finish_time', '', 'Required. Format: DD-MM-YYYY HH:mm');
            }

            $poNumber = trim($row['po_number'] ?? '');
            if ($poNumber === '') {
                $rowErrors[] = $this->formatError($excelRow, 'po_number', $poNumber, 'Required');
            }

            $vehicleNumber = trim($row['vehicle_number'] ?? '');
            if ($vehicleNumber === '') {
                $rowErrors[] = $this->formatError($excelRow, 'vehicle_number', $vehicleNumber, 'Required');
            }

            $vendorName = trim($row['vendor_name'] ?? '');
            if ($vendorName === '') {
                $rowErrors[] = $this->formatError($excelRow, 'vendor_name', $vendorName, 'Required');
            }

            $whCode = trim($row['warehouse_code'] ?? '');
            $whId = null;
            if ($whCode === '') {
                $rowErrors[] = $this->formatError($excelRow, 'warehouse_code', $whCode, 'Required. Options: '.implode(', ', $validWhCodes));
            } elseif (isset($warehouses[strtoupper($whCode)])) {
                $whId = $warehouses[strtoupper($whCode)];
            } else {
                $rowErrors[] = $this->formatError($excelRow, 'warehouse_code', $whCode, 'Warehouse code not found. Options: '.implode(', ', $validWhCodes));
            }

            $gateNumber = trim($row['gate_number'] ?? '');
            $gateId = null;
            if ($gateNumber === '') {
                $rowErrors[] = $this->formatError($excelRow, 'gate_number', $gateNumber, 'Required. Options: '.implode(', ', $validGateNumbers));
            } elseif ($whId) {
                foreach ($allGates as $g) {
                    if (strtoupper($g->gate_number) == strtoupper($gateNumber) && $g->warehouse_id == $whId) {
                        $gateId = $g->id;
                        break;
                    }
                }
                if (! $gateId) {
                    $rowErrors[] = $this->formatError($excelRow, 'gate_number', $gateNumber, 'Gate not found for warehouse '.strtoupper($whCode).'. Options: '.implode(', ', $validGateNumbers));
                }
            }

            // SJ Number validation (Uniqueness)
            $sjNumber = trim($row['sj_number'] ?? '');
            if ($sjNumber !== '') {
                // Check within the excel file
                if (isset($seenSjs[$sjNumber])) {
                    $rowErrors[] = $this->formatError($excelRow, 'sj_number', $sjNumber, "Duplicate SJ Number within this Excel file (Row {$seenSjs[$sjNumber]}).");
                } else {
                    $seenSjs[$sjNumber] = $excelRow;
                    // Check against DB
                    $sjExists = DB::table('slots')->where('mat_doc', $sjNumber)->exists();
                    if ($sjExists) {
                        $rowErrors[] = $this->formatError($excelRow, 'sj_number', $sjNumber, "SJ Number already exists in the database.");
                    }
                }
            }

            // Gate Overlap validation
            if ($gateId && $startStr && $finishStr) {
                if (strtotime($startStr) >= strtotime($finishStr)) {
                    $rowErrors[] = $this->formatError($excelRow, 'finish_time', $finishStr, "Finish time must be after start time.");
                } else {
                    $laneGroup = $slotService->getGateLaneGroup($gateId);
                    $laneGateIds = $laneGroup ? $slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];
                    if (empty($laneGateIds)) {
                        $laneGateIds = [$gateId];
                    }

                    // Check overlap within the excel file
                    $overlapInExcel = false;
                    foreach ($seenSlots as $seenSlot) {
                        if (array_intersect($laneGateIds, $seenSlot['laneGateIds'])) {
                            if (strtotime($seenSlot['start']) < strtotime($finishStr) && strtotime($seenSlot['finish']) > strtotime($startStr)) {
                                $rowErrors[] = $this->formatError($excelRow, 'start_time', $startStr, "Time overlaps with another row in this Excel file (Row {$seenSlot['row']}) on the same gate/lane.");
                                $overlapInExcel = true;
                                break;
                            }
                        }
                    }

                    if (!$overlapInExcel) {
                        $seenSlots[] = [
                            'laneGateIds' => $laneGateIds,
                            'start' => $startStr,
                            'finish' => $finishStr,
                            'row' => $excelRow
                        ];

                        // Check overlap against DB
                        $overlapDb = DB::table('slots')
                            ->whereIn('planned_gate_id', $laneGateIds)
                            ->where('status', '!=', 'cancelled')
                            ->whereRaw("COALESCE(actual_start, planned_start) < ?", [$finishStr])
                            ->whereRaw("COALESCE(actual_finish, {$dateAddExpr}) > ?", [$startStr])
                            ->first(['id', 'ticket_number']);

                        if ($overlapDb) {
                            $ticket = $overlapDb->ticket_number ?? ('Ref #'.$overlapDb->id);
                            $rowErrors[] = $this->formatError($excelRow, 'start_time', $startStr, "Time overlaps with existing transaction in database ({$ticket}) on the same gate/lane.");
                        }
                    }
                }
            }

            // If there are validation errors, add them and skip adding to valid rows
            if (! empty($rowErrors)) {
                $this->errorCount++;
                foreach ($rowErrors as $err) {
                    $this->errors[] = $err;
                }
                continue;
            }

            // If we reached here, the row is valid
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

            $targetDuration = null;
            if ($truckType && isset($truckTargetDurations[strtolower(trim($truckType))])) {
                $targetDuration = $truckTargetDurations[strtolower(trim($truckType))];
            }

            $validRowsToInsert[] = [
                'warehouse_id' => $whId,
                'planned_gate_id' => $gateId,
                'actual_gate_id' => $gateId,
                'arrival_time' => $arrivalStr,
                'planned_start' => $startStr ?: ($arrivalStr ?: now()),
                'planned_duration' => $targetDuration > 0 ? (int) $targetDuration : 0,
                'actual_start' => $startStr,
                'actual_finish' => $finishStr,
                'status' => 'completed',
                'vendor_name' => substr($vendorName, 0, 100),
                'vehicle_number_snap' => substr($vehicleNumber, 0, 50),
                'po_number' => substr($poNumber, 0, 50),
                'driver_name' => substr($row['driver_name'] ?? '', 0, 100) ?: null,
                'driver_number' => substr($row['driver_number'] ?? '', 0, 50) ?: null,
                'mat_doc' => substr($sjNumber, 0, 50) ?: null,
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
            ];
        }

        // All-or-Nothing Approach: Only insert if there are ZERO errors
        if (empty($this->errors) && count($validRowsToInsert) > 0) {
            DB::transaction(function() use ($validRowsToInsert) {
                foreach ($validRowsToInsert as $insertData) {
                    try {
                        DB::table('slots')->insertGetId($insertData);
                        $this->successCount++;
                    } catch (Exception $e) {
                        Log::error('Offline Import Transaction Failed: '.$e->getMessage());
                        throw $e; // Rollback transaction
                    }
                }
            });
        }
    }

    /**
     * Format a structured error message with row, column, cell reference, and value.
     */
    private function formatError(int $row, string $fieldKey, string $value, string $message): array
    {
        $colInfo = self::COLUMN_MAP[$fieldKey] ?? [$fieldKey, '?'];
        $colLabel = $colInfo[0];
        $colLetter = $colInfo[1];

        return [
            'row' => $row,
            'column' => $colLabel,
            'cell' => $colLetter.$row,
            'value' => $value !== '' ? $value : '(kosong)',
            'message' => $message,
        ];
    }

    private function parseDate($str)
    {
        if (! $str) {
            return;
        }
        try {
            // Reject time-only values like "00:00:00" or "12:30:00"
            $strVal = trim((string) $str);
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $strVal)) {
                return;
            }

            // Maatwebsite Excel might parse date to integer timestamp or carbon instance
            if (is_numeric($str)) {
                $dt = Date::excelToDateTimeObject($str);
                // Reject if the result is epoch (1899/1900) — means it was just a time value
                if ((int) $dt->format('Y') < 2000) {
                    return;
                }

                return $dt->format('Y-m-d H:i:s');
            }

            // Convert slashes to dashes: 15/04/2026 -> 15-04-2026 to parse DD-MM-YYYY natively in PHP
            $strVal = str_replace('/', '-', $strVal);

            $dt = new DateTime($strVal);

            // Reject dates before year 2000 (likely parsing errors)
            if ((int) $dt->format('Y') < 2000) {
                return;
            }

            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return;
        }
    }
}
