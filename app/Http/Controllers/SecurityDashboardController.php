<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    /**
     * Security Dashboard — landing page with scan box + today's schedule.
     */
    public function index(Request $request)
    {
        $today = date('Y-m-d');
        $selectedDate = $request->query('date', $today);

        // Validate date format
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = $today;
        }

        $now = now();
        $currentHour = (int) $now->format('H');

        // Determine current shift
        if ($currentHour >= 7 && $currentHour < 15) {
            $shiftLabel = 'Shift 1 (07:00 - 15:00)';
        } elseif ($currentHour >= 15 && $currentHour < 23) {
            $shiftLabel = 'Shift 2 (15:00 - 23:00)';
        } else {
            $shiftLabel = 'Shift 3 (23:00 - 07:00)';
        }

        // Summary counts for selected date
        $dateSlots = DB::table('slots')
            ->whereDate('planned_start', $selectedDate)
            ->where('slot_type', 'planned')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'total' => $dateSlots->sum(),
            'scheduled' => (int) ($dateSlots['scheduled'] ?? 0),
            'waiting' => (int) ($dateSlots['waiting'] ?? 0),
            'in_progress' => (int) ($dateSlots['in_progress'] ?? 0),
            'completed' => (int) ($dateSlots['completed'] ?? 0),
        ];

        // Schedule for selected date
        $schedule = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_gates as pg', 's.planned_gate_id', '=', 'pg.id')
            ->leftJoin('md_warehouse as wpg', 'pg.warehouse_id', '=', 'wpg.id')
            ->whereDate('s.planned_start', $selectedDate)
            ->where('s.slot_type', 'planned')
            ->whereIn('s.status', ['scheduled', 'waiting', 'in_progress', 'completed'])
            ->orderBy('s.planned_start')
            ->select([
                's.id',
                's.ticket_number',
                's.po_number',
                's.vendor_name',
                's.vehicle_number_snap',
                's.direction',
                's.planned_start',
                's.planned_duration',
                's.arrival_time',
                's.status',
                's.is_late',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'pg.gate_number as planned_gate_number',
                'wpg.wh_code as planned_gate_warehouse_code',
            ])
            ->get();

        return view('security.dashboard', [
            'summary' => $summary,
            'schedule' => $schedule,
            'shiftLabel' => $shiftLabel,
            'today' => $today,
            'selectedDate' => $selectedDate,
        ]);
    }

    /**
     * AJAX: Scan ticket number → return slot data + warnings.
     */
    public function scanTicket(Request $request)
    {
        $ticketNumber = trim((string) $request->input('ticket_number', ''));

        if ($ticketNumber === '') {
            return response()->json([
                'success' => false,
                'message' => 'Nomor tiket tidak boleh kosong.',
            ]);
        }

        $slot = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_gates as pg', 's.planned_gate_id', '=', 'pg.id')
            ->leftJoin('md_warehouse as wpg', 'pg.warehouse_id', '=', 'wpg.id')
            ->where('s.ticket_number', $ticketNumber)
            ->select([
                's.id',
                's.ticket_number',
                's.po_number',
                's.vendor_name',
                's.vehicle_number_snap',
                's.driver_name',
                's.direction',
                's.planned_start',
                's.planned_duration',
                's.arrival_time',
                's.status',
                's.slot_type',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'pg.gate_number as planned_gate_number',
                'wpg.wh_code as planned_gate_warehouse_code',
            ])
            ->first();

        if (! $slot) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket "'.$ticketNumber.'" tidak ditemukan dalam sistem.',
            ]);
        }

        // Build warnings
        $warnings = [];
        $canProceed = true;
        $status = (string) ($slot->status ?? '');

        if ($status === 'cancelled') {
            $warnings[] = ['type' => 'error', 'message' => 'Booking ini sudah DIBATALKAN.'];
            $canProceed = false;
        } elseif ($status === 'completed') {
            $warnings[] = ['type' => 'error', 'message' => 'Booking ini sudah SELESAI.'];
            $canProceed = false;
        } elseif (! empty($slot->arrival_time)) {
            $arrivalFormatted = date('H:i', strtotime($slot->arrival_time));
            $warnings[] = ['type' => 'warning', 'message' => 'Sudah dicatat arrival pukul '.$arrivalFormatted.'.'];
            $canProceed = false;
        } elseif ($status !== 'scheduled') {
            $warnings[] = ['type' => 'warning', 'message' => 'Status saat ini: '.ucwords(str_replace('_', ' ', $status)).'.'];
            $canProceed = false;
        }

        // Check if planned for today
        $plannedDate = date('Y-m-d', strtotime($slot->planned_start));
        $today = date('Y-m-d');
        if ($plannedDate !== $today) {
            $warnings[] = ['type' => 'warning', 'message' => 'Booking ini dijadwal untuk tanggal '.date('d-m-Y', strtotime($plannedDate)).', bukan hari ini.'];
        }

        // Check if late (> 15 minutes past ETA)
        $isLate = false;
        if ($status === 'scheduled' && ! empty($slot->planned_start)) {
            $etaPlus15 = strtotime($slot->planned_start) + (15 * 60);
            if (time() > $etaPlus15) {
                $isLate = true;
                $minutesLate = (int) round((time() - strtotime($slot->planned_start)) / 60);
                $warnings[] = ['type' => 'late', 'message' => 'TERLAMBAT '.$minutesLate.' menit dari jadwal.'];
            }
        }

        // Gate display name
        $gateDisplay = '-';
        $whCode = trim((string) ($slot->planned_gate_warehouse_code ?? ''));
        $gateNo = trim((string) ($slot->planned_gate_number ?? ''));
        if ($whCode !== '' && $gateNo !== '') {
            $gateDisplay = $whCode.' - Gate '.$gateNo;
        } elseif (trim((string) ($slot->warehouse_name ?? '')) !== '') {
            $gateDisplay = $slot->warehouse_name;
        }

        return response()->json([
            'success' => true,
            'can_proceed' => $canProceed,
            'is_late' => $isLate,
            'warnings' => $warnings,
            'slot' => [
                'id' => $slot->id,
                'ticket_number' => $slot->ticket_number,
                'po_number' => $slot->po_number,
                'vendor_name' => $slot->vendor_name ?? '-',
                'vehicle_number' => $slot->vehicle_number_snap ?? '-',
                'driver_name' => $slot->driver_name ?? '-',
                'direction' => strtoupper($slot->direction ?? ''),
                'gate' => $gateDisplay,
                'eta' => date('d-m-Y H:i', strtotime($slot->planned_start)),
                'status' => $status,
            ],
        ]);
    }

    /**
     * POST: Confirm arrival for a scanned slot.
     */
    public function confirmArrival(Request $request, int $slotId)
    {
        $slot = DB::table('slots')->where('id', $slotId)->first();

        if (! $slot) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.']);
        }

        if ((string) ($slot->status ?? '') !== 'scheduled') {
            return response()->json(['success' => false, 'message' => 'Hanya booking berstatus "Scheduled" yang bisa dicatat arrival.']);
        }

        if (! empty($slot->arrival_time)) {
            return response()->json(['success' => false, 'message' => 'Arrival sudah dicatat sebelumnya.']);
        }

        $ticketNumber = trim((string) ($slot->ticket_number ?? ''));
        if ($ticketNumber === '') {
            return response()->json(['success' => false, 'message' => 'Slot ini tidak memiliki nomor tiket.']);
        }

        $now = date('Y-m-d H:i:s');

        DB::table('slots')->where('id', $slotId)->update([
            'arrival_time' => $now,
            'ticket_number' => $ticketNumber,
            'status' => 'waiting',
        ]);

        // Log activity
        try {
            DB::table('activity_logs')->insert([
                'slot_id' => $slotId,
                'activity_type' => 'status_change',
                'description' => 'Status Changed to Waiting After Arrival (Security Scan)',
                'created_by' => Auth::id(),
                'created_at' => $now,
            ]);
            DB::table('activity_logs')->insert([
                'slot_id' => $slotId,
                'activity_type' => 'arrival_recorded',
                'description' => 'Arrival Recorded with Ticket '.$ticketNumber.' via Security Dashboard',
                'created_by' => Auth::id(),
                'created_at' => $now,
            ]);
        } catch (\Throwable $e) {
            // Non-critical, continue
        }

        return response()->json([
            'success' => true,
            'message' => 'Kedatangan berhasil dicatat untuk tiket '.$ticketNumber.'.',
        ]);
    }

    /**
     * AJAX: Get today's slots for auto-refresh.
     */
    public function ajaxTodaySlots(Request $request)
    {
        $today = date('Y-m-d');
        $selectedDate = $request->query('date', $today);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = $today;
        }

        $slots = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_gates as pg', 's.planned_gate_id', '=', 'pg.id')
            ->leftJoin('md_warehouse as wpg', 'pg.warehouse_id', '=', 'wpg.id')
            ->whereDate('s.planned_start', $selectedDate)
            ->where('s.slot_type', 'planned')
            ->whereIn('s.status', ['scheduled', 'waiting', 'in_progress', 'completed'])
            ->orderBy('s.planned_start')
            ->select([
                's.id',
                's.ticket_number',
                's.po_number',
                's.vendor_name',
                's.vehicle_number_snap',
                's.direction',
                's.planned_start',
                's.planned_duration',
                's.arrival_time',
                's.status',
                's.is_late',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'pg.gate_number as planned_gate_number',
                'wpg.wh_code as planned_gate_warehouse_code',
            ])
            ->get();

        $counts = $slots->groupBy('status')->map->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'total' => $slots->count(),
                'scheduled' => (int) ($counts['scheduled'] ?? 0),
                'waiting' => (int) ($counts['waiting'] ?? 0),
                'in_progress' => (int) ($counts['in_progress'] ?? 0),
                'completed' => (int) ($counts['completed'] ?? 0),
            ],
            'slots' => $slots->map(function ($s) {
                $gateDisplay = '-';
                $whCode = trim((string) ($s->planned_gate_warehouse_code ?? ''));
                $gateNo = trim((string) ($s->planned_gate_number ?? ''));
                if ($whCode !== '' && $gateNo !== '') {
                    $gateDisplay = $whCode.' - Gate '.$gateNo;
                } elseif (trim((string) ($s->warehouse_name ?? '')) !== '') {
                    $gateDisplay = $s->warehouse_name;
                }

                return [
                    'id' => $s->id,
                    'ticket_number' => $s->ticket_number ?? '-',
                    'po_number' => $s->po_number ?? '-',
                    'vendor_name' => $s->vendor_name ?? '-',
                    'vehicle_number' => $s->vehicle_number_snap ?? '-',
                    'direction' => strtoupper($s->direction ?? ''),
                    'gate' => $gateDisplay,
                    'eta' => date('H:i', strtotime($s->planned_start)),
                    'eta_full' => date('d-m-Y H:i', strtotime($s->planned_start)),
                    'arrival_time' => $s->arrival_time ? date('H:i', strtotime($s->arrival_time)) : null,
                    'status' => $s->status,
                ];
            })->values(),
        ]);
    }

    /**
     * AJAX: Get slot detail by ID for arrival modal.
     */
    public function slotDetail(int $slotId)
    {
        $slot = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_gates as pg', 's.planned_gate_id', '=', 'pg.id')
            ->leftJoin('md_warehouse as wpg', 'pg.warehouse_id', '=', 'wpg.id')
            ->where('s.id', $slotId)
            ->select([
                's.id',
                's.ticket_number',
                's.po_number',
                's.vendor_name',
                's.vehicle_number_snap',
                's.driver_name',
                's.direction',
                's.planned_start',
                's.planned_duration',
                's.arrival_time',
                's.status',
                's.slot_type',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                'pg.gate_number as planned_gate_number',
                'wpg.wh_code as planned_gate_warehouse_code',
            ])
            ->first();

        if (! $slot) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.']);
        }

        $gateDisplay = '-';
        $whCode = trim((string) ($slot->planned_gate_warehouse_code ?? ''));
        $gateNo = trim((string) ($slot->planned_gate_number ?? ''));
        if ($whCode !== '' && $gateNo !== '') {
            $gateDisplay = $whCode.' - Gate '.$gateNo;
        } elseif (trim((string) ($slot->warehouse_name ?? '')) !== '') {
            $gateDisplay = $slot->warehouse_name;
        }

        $status = (string) ($slot->status ?? '');
        $canProceed = $status === 'scheduled' && empty($slot->arrival_time);

        $warnings = [];
        if ($status !== 'scheduled') {
            $statusLabel = ucwords(str_replace('_', ' ', $status));
            $warnings[] = ['type' => 'warning', 'message' => 'Status saat ini: '.$statusLabel.'.'];
        }
        if (! empty($slot->arrival_time)) {
            $warnings[] = ['type' => 'warning', 'message' => 'Sudah dicatat arrival pukul '.date('H:i', strtotime($slot->arrival_time)).'.'];
        }

        $isLate = false;
        if ($status === 'scheduled' && ! empty($slot->planned_start)) {
            $etaPlus15 = strtotime($slot->planned_start) + (15 * 60);
            if (time() > $etaPlus15) {
                $isLate = true;
                $minutesLate = (int) round((time() - strtotime($slot->planned_start)) / 60);
                $warnings[] = ['type' => 'late', 'message' => 'TERLAMBAT '.$minutesLate.' menit dari jadwal.'];
            }
        }

        return response()->json([
            'success' => true,
            'can_proceed' => $canProceed,
            'is_late' => $isLate,
            'warnings' => $warnings,
            'slot' => [
                'id' => $slot->id,
                'ticket_number' => $slot->ticket_number ?? '-',
                'po_number' => $slot->po_number ?? '-',
                'vendor_name' => $slot->vendor_name ?? '-',
                'vehicle_number' => $slot->vehicle_number_snap ?? '-',
                'driver_name' => $slot->driver_name ?? '-',
                'direction' => strtoupper($slot->direction ?? ''),
                'gate' => $gateDisplay,
                'warehouse' => $slot->warehouse_name ?? '-',
                'eta' => ! empty($slot->planned_start) ? date('d-m-Y H:i', strtotime($slot->planned_start)) : '-',
                'status' => $status,
            ],
        ]);
    }
}
