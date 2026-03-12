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
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
        return DB::transaction(function () use ($data, $vendor) {
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
                'approval_notes' => $notes !== '' ? $notes : null,
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
                Log::warning('Failed to send booking submitted notification: '.$e->getMessage());
            }

            return $slot;
        }); // end DB::transaction
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
    ): Slot {
        return DB::transaction(function () use ($slot, $admin, $notes, $approvalAction, $bookingRequestId) {
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

            // Ensure relations needed by notifications are loaded via eager-loading (avoid lazy-load failures)
            try {
                $slot->loadMissing(['plannedGate']);
            } catch (\Throwable $e) {
                // ignore, notification code will handle missing relation gracefully
            }

            // Log booking history
            BookingHistory::logAction(
                $slot->id,
                BookingHistory::ACTION_APPROVED,
                $admin->id,
                Slot::STATUS_SCHEDULED,
                $oldStatus,
                $notes
            );

            // Notify vendor immediately (avoid relying solely on afterCommit which may not fire in some setups)
            $this->notifyVendorApproved($slot, $bookingRequestId, $action === Slot::APPROVAL_RESCHEDULED);

            $slotId = (int) $slot->id;
            $poNumber = (string) ($slot->po_number ?? '');
            $matDoc = $slot->mat_doc ?? null;
            $adminId = (int) $admin->id;
            DB::afterCommit(function () use ($slotId, $poNumber, $matDoc, $adminId) {
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
            });

            return $slot->fresh();
        });
    }

    private function ensurePlannedGateAssigned(Slot $slot): void
    {
        if (! empty($slot->planned_gate_id)) {
            return;
        }

        $warehouseId = (int) ($slot->warehouse_id ?? 0);
        $plannedStart = (string) ($slot->planned_start ?? '');
        $durationMinutes = (int) ($slot->planned_duration ?? 0);
        if ($warehouseId <= 0 || $plannedStart === '' || $durationMinutes <= 0) {
            return;
        }

        $candidateGates = Gate::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->orderBy('gate_number')
            ->get();

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

    private function vendorAlreadyNotifiedApproved(User $vendor, Slot $slot, string $actionUrl): bool
    {
        // IMPORTANT: In Postgres, if notifications.data is TEXT, JSON operators (-> / ->>) will raise an error
        // and abort the surrounding transaction. So we must avoid executing JSON-operator queries in that case.
        $dataType = $this->getNotificationsDataColumnType();
        $isJsonColumn = in_array($dataType, ['json', 'jsonb'], true);

        if ($isJsonColumn) {
            return $vendor->notifications()
                ->where('type', BookingApproved::class)
                ->where(function ($q) use ($slot, $actionUrl) {
                    $q->where('data->slot_id', $slot->id)
                        ->orWhere('data->action_url', $actionUrl);
                })
                ->exists();
        }

        $slotIdNeedle = '"slot_id":'.(int) $slot->id;
        $urlNeedle = '"action_url":"'.str_replace('"', '\\"', $actionUrl).'"';

        return $vendor->notifications()
            ->where('type', BookingApproved::class)
            ->where(function ($q) use ($slotIdNeedle, $urlNeedle) {
                $q->where('data', 'like', '%'.$slotIdNeedle.'%')
                    ->orWhere('data', 'like', '%'.$urlNeedle.'%');
            })
            ->exists();
    }

    private function getNotificationsDataColumnType(): string
    {
        static $cached = null;
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $type = Schema::getColumnType('notifications', 'data');
            $cached = is_string($type) && $type !== '' ? strtolower($type) : 'unknown';

            return $cached;
        } catch (\Throwable $e) {
            $cached = 'unknown';

            return $cached;
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
                'reason' => 'Booking must be within operating hours (07:00 - 23:00)',
            ];
        }

        // Calculate end time
        $endDt = clone $startDt;
        $endDt->modify("+{$durationMinutes} minutes");
        $endHour = (int) $endDt->format('H');

        if ($endHour >= self::OPERATING_END_HOUR && $endDt->format('i') > '00') {
            return [
                'available' => false,
                'reason' => 'Booking must finish before 23:00',
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
                    'reason' => 'This time is blocked while awaiting warehouse team confirmation',
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
                    'reason' => 'This time is already occupied by another booking',
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

                $dateTimeStr = $date.' '.$timeStr.':00';
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
                    'is_available' => ! $isOccupied,
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
            Log::error('Failed to log activity: '.$type.' - '.$e->getMessage(), $context);
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
            Log::warning('Failed to send booking notification: '.$e->getMessage());
        }
    }

    /**
     * Notify vendor about approved booking
     */
    protected function notifyVendorApproved(Slot $slot, ?int $bookingRequestId = null, bool $isRescheduled = false): void
    {
        try {
            Log::info('Dispatching vendor approval notification', [
                'slot_id' => $slot->id,
                'ticket_number' => $slot->ticket_number,
                'requested_by' => $slot->requested_by,
                'booking_request_id' => $bookingRequestId,
                'is_rescheduled' => $isRescheduled,
            ]);

            $vendorId = (int) ($slot->requested_by ?? 0);
            if ($vendorId <= 0) {
                Log::warning('Approval notification skipped: slot has no requested_by', [
                    'slot_id' => $slot->id,
                    'ticket_number' => $slot->ticket_number,
                ]);

                return;
            }

            $vendor = User::find($vendorId);
            if (! $vendor) {
                Log::warning('Approval notification skipped: vendor user not found', [
                    'slot_id' => $slot->id,
                    'ticket_number' => $slot->ticket_number,
                    'vendor_id' => $vendorId,
                ]);

                return;
            }

            if ($vendor) {
                $targetId = $bookingRequestId ?: $slot->id;
                $actionUrl = url('/vendor/bookings/'.$targetId);

                Log::info('Resolved vendor recipient for approval notification', [
                    'slot_id' => $slot->id,
                    'vendor_id' => $vendor->id,
                    'vendor_email' => $vendor->email,
                    'action_url' => $actionUrl,
                ]);

                $alreadyNotified = $this->vendorAlreadyNotifiedApproved($vendor, $slot, $actionUrl);
                if ($alreadyNotified) {
                    Log::info('Approval notification skipped: already notified', [
                        'slot_id' => $slot->id,
                        'vendor_id' => $vendor->id,
                        'action_url' => $actionUrl,
                    ]);

                    return;
                }

                // Ensure gate/warehouse relations are loaded for email content (avoid TBD due to missing relations)
                try {
                    $slot->loadMissing([
                        'plannedGate.warehouse',
                        'actualGate.warehouse',
                        'warehouse',
                    ]);
                } catch (\Throwable $e) {
                    // ignore; email template has fallbacks
                }

                $notification = new BookingApproved($slot, $targetId, $isRescheduled);

                // When calling channels directly (DatabaseChannel/MailChannel), Laravel's NotificationSender
                // does not auto-assign the UUID notification id. Ensure it's set so DB insert won't fail.
                if (empty($notification->id)) {
                    $notification->id = (string) Str::uuid();
                }

                // Always try to store database notification even if email fails.
                $storedDatabase = false;
                try {
                    app(DatabaseChannel::class)->send($vendor, $notification);
                    $storedDatabase = true;

                    Log::info('Stored approval notification (database)', [
                        'slot_id' => $slot->id,
                        'vendor_id' => $vendor->id,
                        'type' => BookingApproved::class,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to store approval notification (database): '.$e->getMessage(), [
                        'slot_id' => $slot->id,
                        'vendor_id' => $vendor->id,
                    ]);
                }

                // Send email only AFTER successful DB commit so it is guaranteed the approval succeeded.
                if ($storedDatabase && ! empty($vendor->email)) {
                    $slotId = (int) $slot->id;
                    $vendorId = (int) $vendor->id;
                    $email = (string) $vendor->email;
                    DB::afterCommit(function () use ($vendor, $notification, $slotId, $vendorId, $email) {
                        try {
                            app(MailChannel::class)->send($vendor, $notification);

                            Log::info('Sent approval notification (mail)', [
                                'slot_id' => $slotId,
                                'vendor_id' => $vendorId,
                                'email' => $email,
                            ]);
                        } catch (\Throwable $e) {
                            Log::warning('Failed to send approval notification (mail): '.$e->getMessage(), [
                                'slot_id' => $slotId,
                                'vendor_id' => $vendorId,
                                'email' => $email,
                            ]);
                        }
                    });
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send approval notification: '.$e->getMessage(), [
                'slot_id' => $slot->id,
                'requested_by' => $slot->requested_by,
            ]);
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
            Log::warning('Failed to send rejection notification: '.$e->getMessage());
        }
    }
}
