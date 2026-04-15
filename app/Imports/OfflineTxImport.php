<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use DateTime;
use Exception;

class OfflineTxImport implements ToCollection, WithHeadingRow
{
    public function __construct()
    {
    }

    /**
    * @param Collection $rows
    */
    public function collection(Collection $rows)
    {
        $warehouses = DB::table('md_warehouse')->pluck('id', 'wh_code')->toArray();
        $gates = DB::table('md_gates')->pluck('id', 'gate_number')->toArray(); // this is rough, since gates are linked to warehouse. We'll do it safely below.
        
        $allGates = DB::table('md_gates')->select('id', 'gate_number', 'warehouse_id')->get();

        foreach ($rows as $index => $row) {
            // Check if row is empty
            if (!isset($row['slot_type_plannedunplanned']) && !isset($row['nomor_po'])) {
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

                if (!$whId || !$gateId) {
                    Log::warning('Offline Import: Warehouse/Gate not found for row ' . $index, $row->toArray());
                    // we still save it? Or skip.
                }

                $arrivalStr = $this->parseDate($row['jam_datang_dd_mm_yyyy_hhmm'] ?? null);
                $startStr = $this->parseDate($row['jam_mulai_dd_mm_yyyy_hhmm'] ?? null);
                $finishStr = $this->parseDate($row['jam_selesai_dd_mm_yyyy_hhmm'] ?? null);

                $duration = 0;
                $leadTime = 0;

                if ($startStr && $finishStr) {
                    $duration = round((strtotime($finishStr) - strtotime($startStr)) / 60);
                }
                
                if ($arrivalStr && $startStr) {
                    $leadTime = round((strtotime($startStr) - strtotime($arrivalStr)) / 60);
                }

                $slotType = strtolower(trim($row['slot_type_plannedunplanned'] ?? 'unplanned'));
                $direction = strtolower(trim($row['direction_inboundoutbound'] ?? 'inbound'));

                DB::table('slots')->insert([
                    'warehouse_id' => $whId,
                    'planned_gate_id' => $gateId,
                    'actual_gate_id' => $gateId,
                    'arrival_time' => $arrivalStr,
                    'actual_start' => $startStr,
                    'actual_finish' => $finishStr,
                    'status' => 'completed',
                    'vendor_name' => substr($row['nama_vendor_customer'] ?? '-', 0, 100),
                    'vehicle_number_snap' => substr($row['nomor_kendaraan'] ?? '-', 0, 50),
                    'po_number' => substr($row['nomor_po'] ?? '-', 0, 50),
                    'driver_name' => substr($row['sopir'] ?? '-', 0, 100),
                    'slot_type' => $slotType === 'planned' ? 'planned' : 'unplanned',
                    'direction' => $direction === 'outbound' ? 'outbound' : 'inbound',
                    'actual_duration_minutes' => $duration > 0 ? (int)$duration : null,
                    'lead_time_minutes' => $leadTime > 0 ? (int)$leadTime : null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'approval_notes' => 'Imported via Offline Excel. ' . ($row['notes'] ?? ''),
                ]);

            } catch (Exception $e) {
                Log::error('Offline Import: Failed to process row ' . $index . ' - ' . $e->getMessage());
            }
        }
    }

    private function parseDate($str)
    {
        if (!$str) return null;
        try {
            // Maatwebsite Excel might parse date to integer timestamp or carbon instance
            if (is_numeric($str)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($str)->format('Y-m-d H:i:s');
            }
            $dt = new DateTime($str);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }
}
