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
        'po_number' => ['PO Number', 'G'],
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
        $gates = DB::table('md_gates')->pluck('id', 'gate_number')->toArray(); // this is rough, since gates are linked to warehouse. We'll do it safely below.

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
                $rowErrors[] = $this->formatError($excelRow, 'slot_type', $slotTypeRaw, 'Wajib diisi. Pilihan: planned, unplanned');
            } elseif (! in_array(strtolower($slotTypeRaw), ['planned', 'unplanned'])) {
                $rowErrors[] = $this->formatError($excelRow, 'slot_type', $slotTypeRaw, 'Nilai tidak valid. Pilihan: planned, unplanned');
            }

            $directionRaw = trim($row['direction'] ?? '');
            if ($directionRaw === '') {
                $rowErrors[] = $this->formatError($excelRow, 'direction', $directionRaw, 'Wajib diisi. Pilihan: inbound, outbound');
            } elseif (! in_array(strtolower($directionRaw), ['inbound', 'outbound'])) {
                $rowErrors[] = $this->formatError($excelRow, 'direction', $directionRaw, 'Nilai tidak valid. Pilihan: inbound, outbound');
            }

            $truckType = trim($row['truck_type'] ?? '');
            if ($truckType === '') {
                $rowErrors[] = $this->formatError($excelRow, 'truck_type', $truckType, 'Wajib diisi. Pilihan: ' . implode(', ', $validTruckTypes));
            } elseif (! isset($truckTargetDurations[strtolower($truckType)])) {
                $rowErrors[] = $this->formatError($excelRow, 'truck_type', $truckType, 'Tipe truk tidak ditemukan di master data. Pilihan: ' . implode(', ', $validTruckTypes));
            }

            $arrivalRaw = $row['arrival_time'] ?? null;
            $arrivalStr = $this->parseDate($arrivalRaw);
            if (! $arrivalStr && $arrivalRaw !== null && trim((string)$arrivalRaw) !== '') {
                $rowErrors[] = $this->formatError($excelRow, 'arrival_time', (string)$arrivalRaw, 'Format tanggal tidak valid. Format: DD-MM-YYYY HH:mm');
            } elseif (! $arrivalStr) {
                $rowErrors[] = $this->formatError($excelRow, 'arrival_time', '', 'Wajib diisi. Format: DD-MM-YYYY HH:mm');
            }

            $startRaw = $row['start_time'] ?? null;
            $startStr = $this->parseDate($startRaw);
            if (! $startStr && $startRaw !== null && trim((string)$startRaw) !== '') {
                $rowErrors[] = $this->formatError($excelRow, 'start_time', (string)$startRaw, 'Format tanggal tidak valid. Format: DD-MM-YYYY HH:mm');
            } elseif (! $startStr) {
                $rowErrors[] = $this->formatError($excelRow, 'start_time', '', 'Wajib diisi. Format: DD-MM-YYYY HH:mm');
            }

            $finishRaw = $row['finish_time'] ?? null;
            $finishStr = $this->parseDate($finishRaw);
            if (! $finishStr && $finishRaw !== null && trim((string)$finishRaw) !== '') {
                $rowErrors[] = $this->formatError($excelRow, 'finish_time', (string)$finishRaw, 'Format tanggal tidak valid. Format: DD-MM-YYYY HH:mm');
            } elseif (! $finishStr) {
                $rowErrors[] = $this->formatError($excelRow, 'finish_time', '', 'Wajib diisi. Format: DD-MM-YYYY HH:mm');
            }

            $poNumber = trim($row['po_number'] ?? '');
            if ($poNumber === '') {
                $rowErrors[] = $this->formatError($excelRow, 'po_number', $poNumber, 'Wajib diisi');
            }

            $vehicleNumber = trim($row['vehicle_number'] ?? '');
            if ($vehicleNumber === '') {
                $rowErrors[] = $this->formatError($excelRow, 'vehicle_number', $vehicleNumber, 'Wajib diisi');
            }

            $vendorName = trim($row['vendor_name'] ?? '');
            if ($vendorName === '') {
                $rowErrors[] = $this->formatError($excelRow, 'vendor_name', $vendorName, 'Wajib diisi');
            }

            $whCode = trim($row['warehouse_code'] ?? '');
            $whId = null;
            if ($whCode === '') {
                $rowErrors[] = $this->formatError($excelRow, 'warehouse_code', $whCode, 'Wajib diisi. Pilihan: ' . implode(', ', $validWhCodes));
            } elseif (isset($warehouses[strtoupper($whCode)])) {
                $whId = $warehouses[strtoupper($whCode)];
            } else {
                $rowErrors[] = $this->formatError($excelRow, 'warehouse_code', $whCode, 'Kode warehouse tidak ditemukan. Pilihan: ' . implode(', ', $validWhCodes));
            }

            $gateNumber = trim($row['gate_number'] ?? '');
            $gateId = null;
            if ($gateNumber === '') {
                $rowErrors[] = $this->formatError($excelRow, 'gate_number', $gateNumber, 'Wajib diisi. Pilihan: ' . implode(', ', $validGateNumbers));
            } elseif ($whId) {
                foreach ($allGates as $g) {
                    if (strtoupper($g->gate_number) == strtoupper($gateNumber) && $g->warehouse_id == $whId) {
                        $gateId = $g->id;
                        break;
                    }
                }
                if (! $gateId) {
                    $rowErrors[] = $this->formatError($excelRow, 'gate_number', $gateNumber, 'Gate tidak ditemukan untuk warehouse ' . strtoupper($whCode) . '. Pilihan: ' . implode(', ', $validGateNumbers));
                }
            }

            // If there are validation errors, skip this row
            if (! empty($rowErrors)) {
                $this->errorCount++;
                foreach ($rowErrors as $err) {
                    $this->errors[] = $err;
                }
                continue;
            }

            try {
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
                    'vendor_name' => substr($vendorName, 0, 100),
                    'vehicle_number_snap' => substr($vehicleNumber, 0, 50),
                    'po_number' => substr($poNumber, 0, 50),
                    'driver_name' => substr($row['driver_name'] ?? '', 0, 100) ?: null,
                    'driver_number' => substr($row['driver_number'] ?? '', 0, 50) ?: null,
                    'mat_doc' => substr($row['sj_number'] ?? '', 0, 50) ?: null,
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
                $this->errors[] = [
                    'row' => $excelRow,
                    'column' => 'N/A',
                    'cell' => 'N/A',
                    'value' => 'N/A',
                    'message' => 'Database error: ' . $e->getMessage(),
                ];
                Log::error('Offline Import: Failed to process row '.$index.' - '.$e->getMessage());
            }
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
            'cell' => $colLetter . $row,
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
