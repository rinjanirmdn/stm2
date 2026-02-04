<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BookingHistory;
use App\Models\Gate;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingRejected;
use App\Notifications\BookingRequested;
use App\Notifications\BookingSubmitted;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BookingApprovalService
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    /**
     * Operating hours for booking
     */
    public const OPERATING_START_HOUR = 7;  // 07:00
    public const OPERATING_END_HOUR = 20;   // 20:00 (still filtered to 19:00 max)

    /**
     * Create a new booking request from vendor
     */
    public function createBookingRequest(array $data, User $vendor): Slot
    {
        // NOTE: Intentionally not wrapped in a transaction due to prior persistence issue.
        // Calculate planned finish
        $plannedFinish = $this->slotService->computePlannedFinish(
            $data['planned_start'],
            (int) $data['planned_duration']
        );

        // Create slot with pending_approval status
        $notes = isset($data['notes']) ? trim((string) $data['notes']) : '';

        $vendorCode = trim((string) ($data['vendor_code'] ?? $vendor->vendor_code ?? ''));
        $vendorName = trim((string) ($data['vendor_name'] ?? $data['supplier_name'] ?? ''));
        $vendorType = trim((string) ($data['vendor_type'] ?? $data['supplier_type'] ?? ''));
        $poNumber = trim((string) ($data['po_number'] ?? ''));

        $slot = Slot::create([
            'ticket_number' => null,
            'direction' => $data['direction'],
            'warehouse_id' => $data['warehouse_id'],
            'po_number' => $poNumber !== '' ? $poNumber : null,
            'vendor_code' => $vendorCode !== '' ? $vendorCode : null,
            'vendor_name' => $vendorName !== '' ? $vendorName : null,
            'vendor_type' => $vendorType !== '' ? $vendorType : null,
            'planned_gate_id' => $data['planned_gate_id'] ?? null,
            'planned_start' => $data['planned_start'],
            'planned_duration' => $data['planned_duration'],
            'truck_type' => $data['truck_type'] ?? null,
            'vehicle_number_snap' => $data['vehicle_number'] ?? null,
            'driver_name' => $data['driver_name'] ?? null,
            'driver_number' => $data['driver_number'] ?? null,
            'late_reason' => $notes !== '' ? $notes : null,
            'status' => Slot::STATUS_PENDING_APPROVAL,
            'slot_type' => 'planned',
            'created_by' => $vendor->id,
            'requested_by' => $vendor->id,
            'requested_at' => now(),
        ]);

        $this->ensurePlannedGateAssigned($slot);

        // Log booking history
        BookingHistory::logAction(
            $slot->id,
            BookingHistory::ACTION_REQUESTED,
            $vendor->id,
            Slot::STATUS_PENDING_APPROVAL,
            null,
            $notes !== '' ? $notes : null,
            [
                'new_planned_start' => $data['planned_start'],
                'new_planned_duration' => $data['planned_duration'],
                'new_gate_id' => $slot->planned_gate_id,
            ]
        );

        // Log activity
        $this->safeActivityLog('booking_requested', [
            'type' => 'booking_requested',
            'description' => "New booking request submitted for PO: {$slot->po_number}",
            'po_number' => $slot->po_number,
            'mat_doc' => $slot->mat_doc ?? null,
            'slot_id' => $slot->id,
            'user_id' => $vendor->id,
        ], [
            'slot_id' => $slot->id,
            'po_number' => $slot->po_number,
            'vendor_id' => $vendor->id,
        ]);

        // Notify admins about new booking request
        $this->notifyAdminsNewBooking($slot);

        // Notify vendor that request was submitted
        try {
            $vendor->notify(new BookingSubmitted($slot));
        } catch (\Throwable $e) {
            Log::warning('Failed to send booking submitted notification: ' . $e->getMessage());
        }

        return $slot;
    }

    /**
     * Approve a booking request
     */
    public function approveBooking(
        Slot $slot,
        User $admin,
        ?string $notes = null,
        ?int $bookingRequestId = null,
        ?string $approvalAction = null
    ): Slot
    {
        return DB::transaction(function () use ($slot, $admin, $notes) {
            $oldStatus = $slot->status;

            $this->ensurePlannedGateAssigned($slot);

            $ticketNumber = $slot->ticket_number;
            if (empty($ticketNumber)) {
                $ticketNumber = $this->slotService->generateTicketNumber(
                    (int) $slot->warehouse_id,
                    $slot->planned_gate_id ? (int) $slot->planned_gate_id : null
                );
            }

            $action = $approvalAction ?: Slot::APPROVAL_APPROVED;

            $slot->update([
                'ticket_number' => $ticketNumber,
                'status' => Slot::STATUS_SCHEDULED,
                'approved_by' => $admin->id,
                'approval_action' => $action,
                'approval_notes' => $notes,
                'approved_at' => now(),
            ]);

            // Log booking history
            BookingHistory::logAction(
                $slot->id,
                BookingHistory::ACTION_APPROVED,
                $admin->id,
                Slot::STATUS_SCHEDULED,
                $oldStatus,
                $notes
            );

            $slotId = (int) $slot->id;
            $poNumber = (string) ($slot->po_number ?? '');
            $matDoc = $slot->mat_doc ?? null;
            $adminId = (int) $admin->id;
            DB::afterCommit(function () use ($slot, $slotId, $poNumber, $matDoc, $adminId, $bookingRequestId, $action) {
                $this->safeActivityLog('booking_approved', [
                    'type' => 'booking_approved',
                    'description' => "Approved booking request for PO: {$poNumber}",
                    'po_number' => $poNumber !== '' ? $poNumber : null,
                    'mat_doc' => $matDoc,
                    'slot_id' => $slotId,
                    'user_id' => $adminId,
                ], [
                    'slot_id' => $slotId,
                    'po_number' => $poNumber,
                    'admin_id' => $adminId,
                ]);

                $this->notifyVendorApproved($slot, $bookingRequestId, $action === Slot::APPROVAL_RESCHEDULED);
            });

            return $slot->fresh();
        });
    }

    private function ensurePlannedGateAssigned(Slot $slot): void
    {
        if (!empty($slot->planned_gate_id)) {
            return;
        }

        $warehouseId = (int) ($slot->warehouse_id ?? 0);
        $plannedStart = (string) ($slot->planned_start ?? '');
        $durationMinutes = (int) ($slot->planned_duration ?? 0);
        if ($warehouseId <= 0 || $plannedStart === '' || $durationMinutes <= 0) {
            return;
        }

        $gatesQ = Gate::where('warehouse_id', $warehouseId);
        if (Schema::hasColumn('md_gates', 'is_active')) {
            $gatesQ->where('is_active', true);
        }
        $candidateGates = $gatesQ->orderBy('gate_number')->get();

        $bestGateId = null;
        $bestRisk = null;
        foreach ($candidateGates as $g) {
            $gid = (int) ($g->id ?? 0);
            if ($gid <= 0) {
                continue;
            }
            $check = $this->checkAvailability(
                $warehouseId,
                $gid,
                $plannedStart,
                $durationMinutes,
                (int) ($slot->id ?? 0)
            );
            if (empty($check['available'])) {
                continue;
            }
            $risk = (int) ($check['blocking_risk'] ?? 0);
            if ($bestGateId === null || $risk < (int) $bestRisk) {
                $bestGateId = $gid;
                $bestRisk = $risk;
            }
        }

        if ($bestGateId !== null) {
            $slot->planned_gate_id = $bestGateId;
            $slot->save();
        }
    }

    /**
     * Reject a booking request
     */
    public function rejectBooking(Slot $slot, User $admin, string $reason): Slot
    {
        return DB::transaction(function () use ($slot, $admin, $reason) {
            $oldStatus = $slot->status;

            $slot->update([
                'status' => Slot::STATUS_CANCELLED,
                'approved_by' => $admin->id,
                'approval_action' => Slot::APPROVAL_REJECTED,
                'approval_notes' => $reason,
                'approved_at' => now(),
                'cancelled_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            // Log booking history
            BookingHistory::logAction(
                $slot->id,
                BookingHistory::ACTION_REJECTED,
                $admin->id,
                Slot::STATUS_CANCELLED,
                $oldStatus,
                $reason
            );

            // Log activity
            $this->safeActivityLog('booking_rejected', [
                'type' => 'booking_rejected',
                'description' => "Rejected booking request for PO: {$slot->po_number} - Reason: {$reason}",
                'po_number' => $slot->po_number,
                'mat_doc' => $slot->mat_doc ?? null,
                'slot_id' => $slot->id,
                'user_id' => $admin->id,
            ], [
                'slot_id' => $slot->id,
                'po_number' => $slot->po_number,
                'admin_id' => $admin->id,
                'reason' => $reason,
            ]);

            // Notify vendor
            $this->notifyVendorRejected($slot);

            return $slot->fresh();
        });
    }

    /**
     * Cancel a booking (by vendor)
     */
    public function cancelBooking(Slot $slot, User $user, string $reason): Slot
    {
        return DB::transaction(function () use ($slot, $user, $reason) {
            $oldStatus = $slot->status;

            $slot->update([
                'status' => Slot::STATUS_CANCELLED,
                'cancelled_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            // Log booking history
            BookingHistory::logAction(
                $slot->id,
                BookingHistory::ACTION_CANCELLED,
                $user->id,
                Slot::STATUS_CANCELLED,
                $oldStatus,
                $reason
            );

            return $slot->fresh();
        });
    }

    /**
     * Check if a time slot is available
     */
    public function checkAvailability(
        int $warehouseId,
        ?int $gateId,
        string $plannedStart,
        int $durationMinutes,
        ?int $excludeSlotId = null
    ): array {
        // Validate operating hours
        $startDt = new DateTime($plannedStart);
        $hour = (int) $startDt->format('H');

        if ($hour < self::OPERATING_START_HOUR || $hour >= self::OPERATING_END_HOUR) {
            return [
                'available' => false,
                'reason' => 'Booking harus dalam jam operasional (07:00 - 23:00)',
            ];
        }

        // Calculate end time
        $endDt = clone $startDt;
        $endDt->modify("+{$durationMinutes} minutes");
        $endHour = (int) $endDt->format('H');

        if ($endHour >= self::OPERATING_END_HOUR && $endDt->format('i') > '00') {
            return [
                'available' => false,
                'reason' => 'Booking harus selesai sebelum jam 23:00',
            ];
        }

        if (empty($gateId)) {
            $startStr = $startDt->format('Y-m-d H:i:s');
            $endStr = $endDt->format('Y-m-d H:i:s');

            $pendingOverlap = $this->slotService->countWarehouseOverlap(
                $warehouseId,
                [Slot::STATUS_PENDING_APPROVAL],
                $startStr,
                $endStr,
                $excludeSlotId
            );
            if ($pendingOverlap > 0) {
                return [
                    'available' => false,
                    'reason' => 'Waktu ini sedang diblokir karena menunggu konfirmasi tim WH',
                    'code' => 'BLOCKED_BY_PENDING_APPROVAL',
                ];
            }

            $activeOverlap = $this->slotService->countWarehouseOverlap(
                $warehouseId,
                [
                    Slot::STATUS_SCHEDULED,
                    Slot::STATUS_ARRIVED,
                    Slot::STATUS_WAITING,
                    Slot::STATUS_IN_PROGRESS,
                ],
                $startStr,
                $endStr,
                $excludeSlotId
            );

            $blockingRisk = $this->slotService->calculateBlockingRisk(
                $warehouseId,
                null,
                $plannedStart,
                $durationMinutes,
                $excludeSlotId
            );

            return [
                'available' => true,
                'needs_confirmation' => ($activeOverlap > 0),
                'blocking_risk' => $blockingRisk,
            ];
        }

        // Check for conflicts if gate is specified
        if ($gateId) {
            $laneGroup = $this->slotService->getGateLaneGroup($gateId);
            $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];

            $overlapCount = $this->slotService->checkLaneOverlap(
                $laneGateIds,
                $startDt->format('Y-m-d H:i:s'),
                $endDt->format('Y-m-d H:i:s'),
                $excludeSlotId
            );

            if ($overlapCount > 0) {
                return [
                    'available' => false,
                    'reason' => 'Waktu ini sudah terisi oleh booking lain',
                ];
            }
        }

        // Calculate blocking risk
        $blockingRisk = $this->slotService->calculateBlockingRisk(
            $warehouseId,
            $gateId,
            $plannedStart,
            $durationMinutes,
            $excludeSlotId
        );

        return [
            'available' => true,
            'blocking_risk' => $blockingRisk,
        ];
    }

    /**
     * Get available time slots for a specific date and gate
     */
    public function getAvailableSlots(int $warehouseId, ?int $gateId, string $date): array
    {
        $slots = [];
        $startHour = self::OPERATING_START_HOUR;
        $endHour = self::OPERATING_END_HOUR;
        $minAllowed = null;
        $todayStr = (new DateTime())->format('Y-m-d');
        if ($date === $todayStr) {
            $minAllowed = new DateTime();
            $minAllowed->modify('+4 hours');
        }

        // Get existing slots for the date
        $existingSlots = DB::table('slots')
            ->where('warehouse_id', $warehouseId)
            ->when($gateId, function ($q) use ($gateId) {
                $q->where('planned_gate_id', $gateId);
            })
            ->whereDate('planned_start', $date)
            ->whereIn('status', [
                Slot::STATUS_PENDING_APPROVAL,
            ])
            ->select(['planned_start', 'planned_duration', 'status'])
            ->get();

        // Generate time slots (30-minute intervals)
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            foreach ([0, 30] as $minute) {
                $timeStr = sprintf('%02d:%02d', $hour, $minute);
                if ($timeStr > '19:00') {
                    continue;
                }

                $dateTimeStr = $date . ' ' . $timeStr . ':00';
                $checkTime = new DateTime($dateTimeStr);
                if ($minAllowed && $checkTime < $minAllowed) {
                    continue;
                }

                $isOccupied = false;
                $occupyingSlot = null;

                foreach ($existingSlots as $existing) {
                    $existingStart = new DateTime($existing->planned_start);
                    $existingEnd = clone $existingStart;
                    $existingEnd->modify("+{$existing->planned_duration} minutes");

                    if ($checkTime >= $existingStart && $checkTime < $existingEnd) {
                        $isOccupied = true;
                        $occupyingSlot = $existing;
                        break;
                    }
                }

                $slots[] = [
                    'time' => $timeStr,
                    'datetime' => $dateTimeStr,
                    'is_available' => !$isOccupied,
                    'status' => $occupyingSlot ? $occupyingSlot->status : null,
                ];
            }
        }

        return $slots;
    }

    private function safeActivityLog(string $type, array $payload, array $context = []): void
    {
        try {
            ActivityLog::create($payload);
        } catch (\Throwable $e) {
            Log::error('Failed to log activity: ' . $type . ' - ' . $e->getMessage(), $context);
        }
    }

    /**
     * Notify admins about new booking request
     */
    protected function notifyAdminsNewBooking(Slot $slot): void
    {
        try {
            $admins = User::whereHas('roles', function ($q) {
                $q->whereIn(DB::raw('LOWER(roles_name)'), [
                    'admin',
                    'section head',
                    'super admin',
                    'super administrator',
                ]);
            })->get();

            if ($admins->isEmpty()) {
                Log::warning('No admin recipients found for booking notification', [
                    'slot_id' => $slot->id,
                    'ticket_number' => $slot->ticket_number,
                ]);
            }

            foreach ($admins as $admin) {
                $admin->notify(new BookingRequested($slot));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send booking notification: ' . $e->getMessage());
        }
    }

    /**
     * Notify vendor about approved booking
     */
    protected function notifyVendorApproved(Slot $slot, ?int $bookingRequestId = null, bool $isRescheduled = false): void
    {
        try {
            $vendor = $slot->requester;
            if ($vendor) {
                $targetId = $bookingRequestId ?: $slot->id;
                $actionUrl = route('vendor.bookings.show', $targetId, false);
                $alreadyNotified = $vendor->notifications()
                    ->where('type', BookingApproved::class)
                    ->where(function ($q) use ($slot, $actionUrl) {
                        $q->where('data->slot_id', $slot->id)
                            ->orWhere('data->action_url', $actionUrl);
                    })
                    ->exists();
                if ($alreadyNotified) {
                    return;
                }
                $vendor->notify(new BookingApproved($slot, $targetId, $isRescheduled));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send approval notification: ' . $e->getMessage());
        }
    }

    /**
     * Notify vendor about rejected booking
     */
    protected function notifyVendorRejected(Slot $slot): void
    {
        try {
            $vendor = $slot->requester;
            if ($vendor) {
                $vendor->notify(new BookingRejected($slot));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send rejection notification: ' . $e->getMessage());
        }
    }
}
