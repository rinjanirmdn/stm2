# Slot Obsolete Logic Documentation

## Problem Statement

User reported an issue where:

- A booking was made for Gate B on date 9th
- The truck arrived earlier on date 7th and started operation
- The original booking for date 9th remained active and caused conflicts when trying to create new bookings
- System should automatically mark old booking as "obsolete" when truck has already started operation

## Solution Implementation

### 1. Enhanced SlotConflictService

**File**: `app/Services/SlotConflictService.php`

#### New Method: `markObsoleteScheduledSlots()`

- Automatically cancels scheduled slots that overlap with completed slots
- Runs during conflict checking to ensure obsolete slots don't block new bookings
- Updates status to 'cancelled' with reason "Auto-cancelled: Truck arrived and completed operation earlier"

#### Modified Method: `hasPotentialConflicts()`

- Added call to `markObsoleteScheduledSlots()` before checking conflicts
- Ensures obsolete slots are cancelled before conflict detection

### 2. Enhanced SlotController

**File**: `app/Http/Controllers/SlotController.php`

#### New Method: `autoCancelObsoleteSlots()`

- Triggered when a slot is marked as 'in_progress' OR 'completed'
- Finds scheduled slots that overlap with current slot's time window
- For in-progress slots: estimates finish time based on planned duration
- For completed slots: uses actual finish time
- Cancels obsolete scheduled slots in same gate/lane group
- Logs auto-cancellation for audit purposes

#### Modified Method: `startStore()`

- Added call to `autoCancelObsoleteSlots()` after starting a slot
- Ensures immediate cleanup of obsolete bookings when operations start

#### Modified Method: `completeStore()`

- Added call to `autoCancelObsoleteSlots()` after completing a slot
- Provides additional cleanup when operations complete

### 3. Test Coverage

**File**: `tests/Feature/SlotObsoleteTest.php`

#### Test Cases:

1. **Auto-cancel obsolete scheduled slots when slot starts**: Verifies scheduled slots are cancelled when overlapping in-progress slot exists
2. **Auto-cancel obsolete scheduled slots when completed slot exists**: Verifies scheduled slots are cancelled when overlapping completed slot exists
3. **Allow new booking after obsolete slot cancelled**: Confirms new bookings can be made after obsolete slots are removed
4. **Don't cancel non-overlapping scheduled slots**: Ensures only overlapping scheduled slots are affected

## Logic Flow

### When Starting a Slot (Primary Trigger):

1. `startStore()` marks slot as 'in_progress'
2. `autoCancelObsoleteSlots()` is triggered with `actualFinish = null`
3. Method estimates finish time based on planned duration
4. Scheduled slots overlapping with estimated time window are cancelled
5. System log records auto-cancellation

### When Completing a Slot (Secondary Trigger):

1. `completeStore()` marks slot as 'completed'
2. `autoCancelObsoleteSlots()` is triggered with actual finish time
3. Scheduled slots overlapping with actual completion time are cancelled
4. System log records auto-cancellation

### When Creating New Booking:

1. `hasPotentialConflicts()` is called
2. `markObsoleteScheduledSlots()` checks for completed slots overlapping with new booking time
3. Any overlapping scheduled slots are marked as 'cancelled'
4. Conflict detection proceeds without obsolete slots blocking new bookings

## Key Features

### Lane Group Awareness

- Respects lane group configurations (e.g., WH2 Gate B/C relationship)
- Only affects slots in the same lane group
- Maintains existing gate coordination rules

### Smart Time Estimation

- For in-progress slots: estimates finish time using planned duration
- Falls back to 1-hour default if no duration specified
- For completed slots: uses actual finish time

### Audit Trail

- All auto-cancellations are logged with clear reasons
- Activity logs track when and why slots were auto-cancelled
- Maintains traceability for operational decisions

### Selective Cancellation

- Only cancels scheduled slots that actually overlap with current operations
- Preserves non-overlapping scheduled bookings
- Minimizes disruption to valid future bookings

## Benefits

1. **Eliminates Double Booking**: Prevents same truck/time slot from being booked multiple times
2. **Improves Gate Utilization**: Frees up time slots when trucks arrive early
3. **Reduces Manual Intervention**: Automatically handles obsolete bookings without admin action
4. **Maintains Audit Trail**: Clear record of all auto-cancellations for compliance
5. **Preserves Existing Logic**: Doesn't change other conflict detection rules
6. **Immediate Response**: Auto-cancellation happens when operation starts, not waits until completion

## Usage Examples

### Scenario 1: Early Arrival

```
Original Booking: Gate B, Jan 9, 10:00-11:00, Status: scheduled
Actual Arrival: Gate B, Jan 7, 10:00, Status: in_progress
Result: Original booking auto-cancelled immediately when operation starts
```

### Scenario 2: Multiple Bookings

```
Booking 1: Gate B, Jan 9, 10:00-11:00, Status: scheduled
Booking 2: Gate B, Jan 9, 14:00-15:00, Status: scheduled
Early Start: Gate B, Jan 7, 10:00, Status: in_progress
Result: Booking 1 cancelled immediately, Booking 2 preserved
```

### Scenario 3: Lane Group Coordination

```
Booking 1: WH2 Gate B, Jan 9, 10:00-11:00, Status: scheduled
Booking 2: WH2 Gate C, Jan 9, 10:00-11:00, Status: scheduled
Early Start: WH2 Gate B, Jan 7, 10:00, Status: in_progress
Result: Only Booking 1 (same gate) cancelled, Booking 2 (different gate) preserved
```

## Configuration

The logic works with existing gate configurations and lane groups. No additional configuration is required.

## Testing

Run test suite to verify functionality:

```bash
php artisan test tests/Feature/SlotObsoleteTest.php
```

## Future Enhancements

1. **Notification System**: Alert users when their bookings are auto-cancelled
2. **Grace Period**: Option to delay auto-cancellation by configurable time
3. **Manual Override**: Ability for admins to restore auto-cancelled bookings if needed
4. **Batch Processing**: Periodic cleanup of obsolete slots across all gates
