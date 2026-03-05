# Technical Forensic Audit Report - Part 3
## e-Docking Control System (STM2)
**Date:** 2026-03-04

---

## Temuan #31 — Triple Notification Classes untuk Booking

**Lokasi:** `app/Notifications/BookingSubmitted.php`, `BookingRequested.php`, `BookingRequestSubmitted.php`

**Masalah:** Tiga notification classes untuk fungsi sangat mirip:
1. `BookingSubmitted` — notify vendor (uses Slot)
2. `BookingRequested` — notify admin (uses Slot)
3. `BookingRequestSubmitted` — notify admin (uses BookingRequest)

`BookingRequested` dan `BookingRequestSubmitted` keduanya notify admin tentang booking baru, tapi model berbeda.

**Penjelasan teknis:** Dua code path terpisah notify admin: `BookingApprovalService` dispatch `BookingRequested`, `VendorBookingController` dispatch `BookingRequestSubmitted`. Admin **mungkin menerima dua notifikasi** untuk satu booking.

**Dampak:** Double notification ke admin. Inconsistent notification format. Maintenance burden.

**Tingkat keparahan:** High

**Rekomendasi:** Audit apakah keduanya terpicu bersamaan. Konsolidasi menjadi satu notification per event. Dispatch hanya dari service layer.

---

## Temuan #32 — Notification Dispatch di Controller DAN Service

**Lokasi:** `app/Http/Controllers/BookingApprovalController.php` (baris 525-528), `app/Services/BookingApprovalService.php`

**Masalah:** BookingApprovalController langsung membuat `BookingRejected` notification (baris 528), sementara BookingApprovalService juga handle notification dispatch. Notification logic tersebar di dua layer.

**Penjelasan teknis:** Controller membuat `tempSlot` palsu (Slot baru tanpa save) dengan `$tempSlot->id = $bookingRequest->id` untuk mengirim notification. Ini anti-pattern — ID slot tidak match dengan booking request ID. Link di notification email akan salah.

**Dampak:** 
- Potensi double notification reject.
- Notification link mengarah ke URL yang salah (booking ID bukan slot ID).

**Tingkat keparahan:** High

**Rekomendasi:** Centralize notification dispatch di service layer. Buat notification class yang accept BookingRequest model langsung, bukan fake Slot.

---

## Temuan #33 — One-Time Commands Masih Ada

**Lokasi:** `app/Console/Commands/BackfillBookingNotifications.php`, `FixBlockingRisk.php`

**Masalah:** One-time migration/fix commands yang kemungkinan sudah dijalankan.

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus atau tambahkan guard check agar tidak berjalan dua kali.

---

## Temuan #34 — 56 Migration Files, Banyak Fix/Backfill

**Lokasi:** `database/migrations/` (56 files)

**Masalah:** Banyak migration yang bersifat:
- Permission adjustment (10+ files)
- Contradictory: `add_po_number` lalu `remove_po_number`
- Backfill data (4 files)
- Schema rename, table drop

**Dampak:** Slow migration saat fresh install. Sulit memahami current schema. Risk saat re-run backfill migrations.

**Tingkat keparahan:** Medium

**Rekomendasi:** Sebelum production: squash migrations per tabel. Hapus one-time backfill migrations. Update DATABASE_SCHEMA.md.

---

## Temuan #35 — createBookingRequest Tanpa Transaction

**Lokasi:** `app/Services/BookingApprovalService.php` (baris 36)

**Masalah:** Method `createBookingRequest()` tidak di-wrap dalam `DB::transaction()`. Komentar: "Intentionally not wrapped in a transaction due to prior persistence issue."

**Penjelasan teknis:** Tanpa transaction, jika slot berhasil dibuat tapi booking history atau activity log gagal, data inconsistent: slot ada tanpa history. Komentar menunjukkan workaround, bukan fix root cause.

**Dampak:** Data inconsistency risk pada partial failure.

**Tingkat keparahan:** High

**Rekomendasi:** Investigasi "prior persistence issue". Fix root cause. Wrap dalam `DB::transaction()`.

---

## Temuan #36 — Holiday Map Logic Duplikasi 4 Tempat

**Lokasi:**
- `app/Http/Controllers/BookingApprovalController.php` (baris 694-700)
- `app/Http/Controllers/DashboardController.php` (baris 340-346)
- `app/Http/Controllers/VendorBookingController.php` (baris 670-677)
- `app/Providers/AppServiceProvider.php` (baris 27-43)

**Masalah:** Exact same holiday map generation logic copy-pasted di 4 tempat.

**Dampak:** DRY violation. Perubahan harus di 4 tempat. Bug risk jika satu diupdate tapi lainnya tidak.

**Tingkat keparahan:** Medium

**Rekomendasi:** Buat `HolidayHelper::getHolidayMap(string $date): array` dan gunakan di semua tempat.

---

## Temuan #37 — Holiday Helper Wrapper Duplikasi

**Lokasi:** `app/Services/SlotService.php` (baris 851-887), `app/Services/ScheduleTimelineService.php` (baris 52-71)

**Masalah:** 8 wrapper methods (4 per service) yang hanya memanggil `HolidayHelper::method()`.

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus wrapper methods. Gunakan `HolidayHelper` langsung.

---

## Temuan #38 — Delegate Private Methods di SlotController

**Lokasi:** `app/Http/Controllers/SlotController.php` (baris 245-268)

**Masalah:** 6 private methods yang hanya forwarding ke injected service (1-line each): `minutesDiff()`, `isLateByPlannedStart()`, `getPlannedDurationForStart()`, `findInProgressConflicts()`, `buildConflictLines()`, `getTruckTypeOptions()`.

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus delegates. Panggil `$this->timeService->...` dan `$this->conflictService->...` langsung.

---

## Temuan #39 — po_number Dialiaskan Sebagai truck_number

**Lokasi:** `app/Http/Controllers/SlotController.php` (baris 101, 221)

**Masalah:** SQL query menaliaskan `s.po_number as truck_number`. PO Number dan Truck Number adalah entitas berbeda.

**Dampak:** Developer confusion. Potensi bug jika `truck_number` dipakai sebagai nomor truk.

**Tingkat keparahan:** Medium

**Rekomendasi:** Gunakan alias yang benar. Refactor semua referensi `truck_number` yang merujuk ke PO Number.

---

## Temuan #40 — .env vs .env.example Mismatch

**Lokasi:** `.env` dan `.env.example`

**Masalah:**
1. `.env` = `pgsql`, `.env.example` = `mysql` — database driver mismatch
2. `.env` tidak punya `BROADCAST_CONNECTION`, `FILESYSTEM_DISK`, `QUEUE_CONNECTION`
3. `.env.example` punya `DEFAULT_USER_PASSWORD` yang tidak ada di `.env`
4. `.env` punya `ADMIN_EMAIL`, Reverb config yang tidak ada di `.env.example`

**Dampak:** Developer baru akan gagal setup karena `.env.example` tidak mencerminkan actual config.

**Tingkat keparahan:** Medium

**Rekomendasi:** Sinkronkan `.env.example` dengan semua variabel yang dibutuhkan.

---

## Temuan #41 — Information Disclosure di 403 Error

**Lokasi:** `app/Http/Middleware/RoleMiddleware.php` (baris 50)

**Masalah:** Error message mengekspos role yang dibutuhkan dan role user saat ini:
```
You do not have the required role (admin, security). Your role is: operator
```

**Dampak:** Attacker bisa memetakan role structure dari pesan error.

**Tingkat keparahan:** Medium

**Rekomendasi:** Gunakan pesan generik: `abort(403, 'Unauthorized.')`. Log detail ke server log.

---

## Temuan #42 — VendorBookingController 42KB

**Lokasi:** `app/Http/Controllers/VendorBookingController.php` (42,081 bytes)

**Masalah:** Controller besar menangani dashboard, CRUD booking, AJAX, availability, calendar, PO search. Duplikasi logika dengan `BookingApprovalController` dan `SlotController`.

**Tingkat keparahan:** Medium

**Rekomendasi:** Split ke `VendorDashboardController`, `VendorBookingController` (CRUD only), `VendorAjaxController`.

---

## Temuan #43 — Large Service Files (30KB+)

**Lokasi:** `app/Services/DashboardStatsService.php` (32KB), `ScheduleTimelineService.php` (30KB), `SlotService.php` (31KB)

**Masalah:** Tiga service files masing-masing > 30KB. Melanggar SRP, sulit di-test dan di-maintain.

**Tingkat keparahan:** Medium

**Rekomendasi:** Split berdasarkan responsibility. Contoh: `DashboardStatsService` → `KpiService`, `ChartDataService`, `StatusSummaryService`.

---

## Temuan #44 — SAP Service Tiga File Overlap

**Lokasi:** `app/Services/SapService.php`, `SapPoService.php`, `SapVendorService.php`

**Masalah:** Tiga SAP service dengan fungsi overlapping:
- `SapService` — legacy, demo mode, direct `env()` calls
- `SapPoService` — newer, uses `config()`, has dummy data
- `SapVendorService` — vendor-specific

`SapController` inject ketiga service dan uses `SapService` sebagai fallback.

**Dampak:** Maintenance confusion, inconsistent patterns.

**Tingkat keparahan:** Medium

**Rekomendasi:** Consolidate `SapService` into `SapPoService`. Hapus legacy `SapService`.

---

## Temuan #45 — LIKE Query Without Wildcard Escaping

**Lokasi:** Multiple controllers — `BookingApprovalController`, `SlotController`, `ReportController`, etc.

**Masalah:** User input langsung di-concatenate ke LIKE pattern tanpa escaping `%` dan `_` wildcards. Pattern tersebar di banyak controller.

**Penjelasan teknis:** Tidak menyebabkan SQL injection (PDO binding), tapi user bisa manipulasi search results via wildcard chars.

**Tingkat keparahan:** Low

**Rekomendasi:** Escape wildcards: `str_replace(['%', '_'], ['\\%', '\\_'], $input)`.

---

## Temuan #46 — Unused Config Sections

**Lokasi:** `config/services.php` (baris 17-36)

**Masalah:** Config untuk Postmark, Resend, SES, Slack — semua tidak digunakan (default Laravel).

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus unused config sections.

---

## Temuan #47 — vendor.js 44KB Monolith

**Lokasi:** `resources/js/pages/vendor.js` (44,617 bytes)

**Masalah:** Monolith JS file untuk semua vendor pages.

**Tingkat keparahan:** Low

**Rekomendasi:** Split berdasarkan page.

---

## Temuan #48 — APP_DEBUG=true dan APP_ENV=local

**Lokasi:** `.env` (baris 2-4)

**Masalah:** Debug mode aktif dan environment set ke local. Jika ini deployment ke IP `192.104.203.69:8000` yang bisa diakses network, error messages detail termasuk stack traces akan terexpose.

**Penjelasan teknis:** `APP_DEBUG=true` menampilkan full stack trace, query details, dan file paths di error page. `APP_ENV=local` juga mempengaruhi behavior debug tools.

**Dampak:** Information disclosure serius jika accessible dari network lain.

**Tingkat keparahan:** High (jika accessible dari network) / Medium (jika hanya development)

**Rekomendasi:** Set `APP_DEBUG=false` dan `APP_ENV=production` di server yang bisa diakses user lain. Gunakan separate `.env` per environment.

---

## Temuan #49 — debugbar di require-dev

**Lokasi:** `composer.json` (baris 19)

**Masalah:** `barryvdh/laravel-debugbar` di `require-dev`. Jika `composer install` dijalankan tanpa `--no-dev` di production dengan `APP_DEBUG=true`, debugbar akan aktif dan menampilkan queries, requests, dan data sensitif.

**Tingkat keparahan:** Medium

**Rekomendasi:** Pastikan production deployment menggunakan `composer install --no-dev`.

---

## Temuan #50 — Unplanned Routes Tanpa Permission Middleware

**Lokasi:** `routes/web.php` (baris 130-133)

**Masalah:** Unplanned start dan complete routes **tidak memiliki permission middleware**:
```php
Route::get('/{slotId}/start', ...)->name('start');
Route::post('/{slotId}/start', ...)->name('start.store');
Route::get('/{slotId}/complete', ...)->name('complete');
Route::post('/{slotId}/complete', ...)->name('complete.store');
```

Bandingkan dengan planned slots yang punya `->middleware('permission:slots.start')`.

**Penjelasan teknis:** Siapapun yang authenticated bisa start dan complete unplanned slots tanpa permission check. Komentar di code: "operator can use these" — tapi tidak ada guard yang memastikan hanya operator dan admin.

**Dampak:** Authorization bypass — semua authenticated users (termasuk vendor) bisa manipulasi unplanned slots.

**Tingkat keparahan:** High

**Rekomendasi:** Tambahkan permission middleware yang sesuai: `->middleware('permission:unplanned.start')` dll.

---

# Ringkasan Severity

| Severity | Jumlah |
|----------|--------|
| Critical | 1 |
| High | 9 |
| Medium | 22 |
| Low | 18 |
| **Total** | **50** |

---

# Prioritas Perbaikan

## Immediate (Critical + High)
1. **#1** Rotasi semua kredensial yang terekspos
2. **#5** Fix route gates.toggle override bug
3. **#50** Tambahkan permission middleware ke unplanned routes
4. **#48** Set APP_DEBUG=false jika accessible dari network
5. **#3** Hapus test_mail.php
6. **#31** Fix double notification ke admin
7. **#32** Fix fake Slot notification (wrong URL)
8. **#35** Wrap createBookingRequest dalam transaction
9. **#22** Fix SapService usleep() dan env() calls
10. **#13** Mulai audit dan reduce style.css

## Short-term (Medium)
11-32. Fix semua temuan Medium sesuai urutan dampak

## Long-term (Low)
33-50. Cleanup dead code, fix minor inconsistencies
