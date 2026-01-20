# Vendor Self-Booking Implementation Plan

## 📋 Overview
Menambahkan fitur self-booking untuk vendor/supplier/customer dengan sistem approval dua arah antara Vendor dan Admin/Section Head.

---

## 🎯 Goals
1. Vendor bisa login dengan akun sendiri dan mengajukan booking slot
2. Admin/Section Head bisa approve/reject/reschedule booking yang diajukan
3. Jika Admin reschedule, vendor harus konfirmasi
4. Vendor bisa melihat ketersediaan slot (jam kosong)
5. Tampilan calendar timeline seperti Dock Management (tapi pakai Gate A, B, C)

---

## 🔄 Booking Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           VENDOR BOOKING FLOW                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  VENDOR                          ADMIN/SECTION HEAD                          │
│  ──────                          ──────────────────                          │
│                                                                              │
│  ┌──────────────┐                                                           │
│  │ Create       │                                                           │
│  │ Booking      │─────────────────┐                                         │
│  │ Request      │                 │                                         │
│  └──────────────┘                 ▼                                         │
│                           ┌──────────────────┐                              │
│                           │ pending_approval │                              │
│                           └────────┬─────────┘                              │
│                                    │                                         │
│                    ┌───────────────┼───────────────┐                        │
│                    ▼               ▼               ▼                        │
│             ┌──────────┐    ┌──────────┐    ┌──────────┐                   │
│             │ APPROVE  │    │ REJECT   │    │RESCHEDULE│                   │
│             └────┬─────┘    └────┬─────┘    └────┬─────┘                   │
│                  │               │               │                          │
│                  ▼               ▼               ▼                          │
│           ┌──────────┐    ┌──────────┐   ┌─────────────────┐               │
│           │scheduled │    │ rejected │   │pending_vendor   │               │
│           └──────────┘    └────┬─────┘   │_confirmation    │               │
│                                │         └────────┬────────┘               │
│                                ▼                  │                         │
│                         ┌──────────────┐          │                         │
│                         │Vendor dapat  │          │                         │
│                         │re-submit     │          │                         │
│                         │booking baru  │          ▼                         │
│                         └──────────────┘   ┌─────────────┐                 │
│                                            │VENDOR       │                 │
│                                            │CONFIRMS     │                 │
│                                            └──────┬──────┘                 │
│                                       ┌──────────┼──────────┐              │
│                                       ▼          ▼          ▼              │
│                                 ┌─────────┐ ┌─────────┐ ┌─────────┐       │
│                                 │ ACCEPT  │ │ REJECT  │ │RE-PROP  │       │
│                                 └────┬────┘ └────┬────┘ └────┬────┘       │
│                                      │           │           │             │
│                                      ▼           ▼           ▼             │
│                                ┌──────────┐┌──────────┐┌──────────────┐   │
│                                │scheduled ││cancelled ││pending_      │   │
│                                └──────────┘└──────────┘│approval      │   │
│                                                        │(new schedule)│   │
│                                                        └──────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 🗄️ Database Changes

### 1. Update `users` table
```sql
-- Tambah kolom untuk link ke vendor
ALTER TABLE users ADD COLUMN vendor_id BIGINT UNSIGNED NULL;
ALTER TABLE users ADD FOREIGN KEY (vendor_id) REFERENCES vendors(id);
```

### 2. Update `slots` table - New Statuses
```php
// Current statuses:
'scheduled', 'arrived', 'waiting', 'in_progress', 'completed', 'cancelled'

// New statuses to add:
'pending_approval',           // Vendor submitted, waiting admin approval
'rejected',                   // Admin rejected
'pending_vendor_confirmation', // Admin rescheduled, waiting vendor confirmation
```

### 3. Add new columns to `slots` table
```sql
-- Tracking approval history
ALTER TABLE slots ADD COLUMN requested_by BIGINT UNSIGNED NULL;      -- Vendor user who requested
ALTER TABLE slots ADD COLUMN approved_by BIGINT UNSIGNED NULL;       -- Admin who approved
ALTER TABLE slots ADD COLUMN approval_action VARCHAR(50) NULL;       -- approved, rejected, rescheduled
ALTER TABLE slots ADD COLUMN approval_notes TEXT NULL;               -- Admin notes
ALTER TABLE slots ADD COLUMN requested_at TIMESTAMP NULL;            -- When vendor requested
ALTER TABLE slots ADD COLUMN approved_at TIMESTAMP NULL;             -- When admin approved
ALTER TABLE slots ADD COLUMN vendor_confirmed_at TIMESTAMP NULL;     -- When vendor confirmed reschedule

-- Original requested schedule (before admin reschedule)
ALTER TABLE slots ADD COLUMN original_planned_start TIMESTAMP NULL;
ALTER TABLE slots ADD COLUMN original_planned_gate_id BIGINT UNSIGNED NULL;
```

### 4. New table: `booking_histories`
```sql
CREATE TABLE booking_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL,         -- requested, approved, rejected, rescheduled, vendor_confirmed, vendor_rejected
    performed_by BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    old_planned_start TIMESTAMP NULL,
    new_planned_start TIMESTAMP NULL,
    old_gate_id BIGINT UNSIGNED NULL,
    new_gate_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (slot_id) REFERENCES slots(id),
    FOREIGN KEY (performed_by) REFERENCES users(id)
);
```

---

## 👥 Roles & Permissions

### New Role: `vendor`
```php
// Permissions for vendor role:
'bookings.index'              // View own bookings list
'bookings.create'             // Create booking request
'bookings.view'               // View booking detail
'bookings.cancel'             // Cancel own pending booking
'bookings.confirm'            // Confirm/reject admin reschedule
'slots.availability'          // View slot availability calendar
```

### Updated Admin Permissions:
```php
// Additional permissions for admin:
'bookings.approve'            // Approve vendor booking
'bookings.reject'             // Reject vendor booking  
'bookings.reschedule'         // Reschedule vendor booking
'bookings.manage'             // View all vendor bookings
```

---

## 📁 New Files Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── VendorBookingController.php      # Vendor booking actions
│   ├── Middleware/
│   │   └── VendorMiddleware.php             # Check vendor role & active status
│   └── Requests/
│       ├── BookingRequest.php               # Validate booking submission
│       └── BookingApprovalRequest.php       # Validate approval action
├── Models/
│   ├── BookingHistory.php                   # Booking history model
│   └── User.php                             # Add vendor relationship
├── Services/
│   └── BookingApprovalService.php           # Business logic for approval flow
│
resources/views/
├── vendor/                                   # Vendor portal views
│   ├── dashboard.blade.php                  # Vendor dashboard
│   ├── bookings/
│   │   ├── index.blade.php                  # My bookings list
│   │   ├── create.blade.php                 # Create booking form
│   │   ├── show.blade.php                   # Booking detail
│   │   ├── confirm.blade.php                # Confirm admin reschedule
│   │   └── availability.blade.php           # View available slots
│   └── layouts/
│       └── vendor.blade.php                 # Vendor layout (simpler)
│
├── admin/
│   └── bookings/
│       ├── index.blade.php                  # All pending bookings
│       ├── show.blade.php                   # Booking detail + actions
│       ├── approve.blade.php                # Approve form
│       ├── reject.blade.php                 # Reject form
│       └── reschedule.blade.php             # Reschedule form
│
├── components/
│   └── calendar-timeline.blade.php          # Reusable calendar component

routes/
└── web.php                                  # Add vendor routes
```

---

## 🎨 UI Components

### 1. Calendar Timeline View (Like Reference Image)
- Header: Gate A | Gate B | Gate C
- Left column: Time slots (07:00, 08:00, 09:00, ...)
- Grid cells: Show bookings with status colors
- Click empty cell → Add booking (for vendor)
- Click booking → View detail

### 2. Status Color Coding
| Status | Color | Label |
|--------|-------|-------|
| `pending_approval` | 🟡 Yellow/Amber | Pre-Booking |
| `pending_vendor_confirmation` | 🟠 Orange | Needs Confirmation |
| `scheduled` | 🟢 Green | Confirmed |
| `in_progress` | 🔵 Blue | In Progress |
| `completed` | ⚫ Gray | Completed |
| `rejected` | 🔴 Red | Rejected |
| `cancelled` | ⚪ Light Gray | Cancelled |

### 3. Booking Form Fields (Vendor)
- **PO Number** (optional, can search from SAP)
- **Direction**: Inbound / Outbound
- **Truck Type** (dropdown)
- **Vehicle Number** (optional at booking, required at arrival)
- **Preferred Gate** (optional, can let system recommend)
- **Preferred Date** (date picker)
- **Preferred Time** (time picker, show availability)
- **Notes** (optional)

---

## 🛣️ Routes Plan

```php
// Vendor Routes
Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/dashboard', [VendorBookingController::class, 'dashboard'])->name('dashboard');
    
    // Bookings
    Route::get('/bookings', [VendorBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [VendorBookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [VendorBookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{id}', [VendorBookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{id}/cancel', [VendorBookingController::class, 'cancel'])->name('bookings.cancel');
    
    // Confirm/Reject Admin Reschedule
    Route::get('/bookings/{id}/confirm', [VendorBookingController::class, 'confirmForm'])->name('bookings.confirm');
    Route::post('/bookings/{id}/confirm', [VendorBookingController::class, 'confirmStore'])->name('bookings.confirm.store');
    
    // Availability
    Route::get('/availability', [VendorBookingController::class, 'availability'])->name('availability');
    Route::get('/availability/slots', [VendorBookingController::class, 'getAvailableSlots'])->name('availability.slots');
});

// Admin Booking Management Routes
Route::middleware(['auth', 'permission:bookings.manage'])->prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [BookingApprovalController::class, 'index'])->name('index');
    Route::get('/{id}', [BookingApprovalController::class, 'show'])->name('show');
    Route::post('/{id}/approve', [BookingApprovalController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [BookingApprovalController::class, 'reject'])->name('reject');
    Route::get('/{id}/reschedule', [BookingApprovalController::class, 'rescheduleForm'])->name('reschedule');
    Route::post('/{id}/reschedule', [BookingApprovalController::class, 'reschedule'])->name('reschedule.store');
});
```

---

## 📋 Implementation Phases

### Phase 1: Database & Models (Est: 1 hour)
- [ ] Create migration for slots table changes
- [ ] Create migration for users.vendor_id
- [ ] Create migration for booking_histories table
- [ ] Update Slot model with new relationships
- [ ] Create BookingHistory model
- [ ] Update User model with vendor relationship

### Phase 2: Roles & Permissions (Est: 30 min)
- [ ] Add vendor role
- [ ] Add new permissions
- [ ] Create VendorMiddleware
- [ ] Update seeder

### Phase 3: Vendor Portal - Backend (Est: 2 hours)
- [ ] Create VendorBookingController
- [ ] Implement booking creation logic
- [ ] Implement availability check logic
- [ ] Implement confirmation logic
- [ ] Add routes

### Phase 4: Admin Approval - Backend (Est: 1.5 hours)
- [ ] Create BookingApprovalController
- [ ] Create BookingApprovalService
- [ ] Implement approve/reject/reschedule logic
- [ ] Add routes

### Phase 5: Vendor Portal - Frontend (Est: 3 hours)
- [ ] Create vendor layout
- [ ] Create vendor dashboard
- [ ] Create booking list view
- [ ] Create booking form
- [ ] Create availability calendar view
- [ ] Create confirmation page

### Phase 6: Admin Booking Management - Frontend (Est: 2 hours)
- [ ] Create booking list for admin
- [ ] Create approval/rejection forms
- [ ] Create reschedule form
- [ ] Add booking section to sidebar

### Phase 7: Calendar Timeline Component (Est: 2.5 hours)
- [ ] Create calendar timeline component (like reference image)
- [ ] Implement gate columns
- [ ] Implement time rows
- [ ] Add interactivity (click to book, click to view)
- [ ] Add status color coding
- [ ] Make responsive

### Phase 8: Testing & Polish (Est: 1.5 hours)
- [ ] Test full booking flow
- [ ] Test approval flow
- [ ] Test reschedule confirmation flow
- [ ] Fix bugs
- [ ] Add notifications/alerts

---

## ⏱️ Total Estimated Time: ~14 hours

---

## ❓ Questions Before Starting

1. **Notifications**: Perlu email/SMS notification saat booking di-approve/reject? Atau cukup di dashboard saja?

2. **Operating Hours**: Jam operasional loading gate dari jam berapa sampai jam berapa? (untuk validasi booking)

3. **Slot Duration**: Durasi default satu slot berapa menit? (misal: 30 menit, 1 jam?)

4. **Concurrent Bookings**: Berapa maksimal booking yang bisa berjalan bersamaan di satu gate?

5. **Advance Booking**: Vendor bisa booking berapa hari ke depan maksimal?

---

## ✅ Ready to Start?

Jika plan ini sudah sesuai, saya akan mulai dari **Phase 1: Database & Models**.
