# Technical Forensic Audit Report - Part 2
## e-Docking Control System (STM2)
**Date:** 2026-03-04

---

## Temuan #16 — main.js 91KB Monolith

**Lokasi:** `resources/js/pages/main.js` (91,084 bytes)

**Masalah:** File JS utama berukuran 91KB — monolith yang berisi semua utility, UI logic, dan initialization code.

**Dampak:** Maintainability buruk, performance impact, perubahan kecil bisa side effect.

**Tingkat keparahan:** Medium

**Rekomendasi:** Split menjadi modul-modul berdasarkan fungsi (utility, UI components, initialization).

---

## Temuan #17 — dashboard.jsx 83KB Single File

**Lokasi:** `resources/js/react/dashboard.jsx` (83,376 bytes)

**Masalah:** Seluruh React dashboard dalam satu file 83KB. Kemungkinan berisi 10+ sub-components, utility functions, dan data processing.

**Dampak:** HMR lambat, code review impossible, maintenance sulit.

**Tingkat keparahan:** Medium

**Rekomendasi:** Split ke sub-components: `DashboardStats.jsx`, `DashboardChart.jsx`, `DashboardTimeline.jsx`, dll. Target < 500 baris per file.

---

## Temuan #18 — Vendor Profile View Tanpa Route

**Lokasi:** `resources/views/vendor/profile/index.blade.php`

**Masalah:** View file ada (6,780 bytes) tapi tidak ada route `vendor.profile` di `web.php`. View ini tidak bisa diakses.

**Penjelasan teknis:** Vendor user menggunakan route `profile` di `ProfileController` yang menggunakan layout admin, bukan vendor layout. View ini menggunakan `@extends('vendor.layouts.vendor')` yang benar untuk vendor, tapi tidak pernah di-render.

**Dampak:** Dead view. Vendor mungkin melihat profile dengan layout admin yang tidak konsisten.

**Tingkat keparahan:** Medium

**Rekomendasi:** Tambahkan route vendor profile, atau hapus file jika vendor menggunakan profile page yang sama.

---

## Temuan #19 — BookingHistory: Dead Vendor Confirmation Constants

**Lokasi:** `app/Models/BookingHistory.php` (baris 40-43)

**Masalah:** Constants `ACTION_VENDOR_CONFIRMED`, `ACTION_VENDOR_REJECTED`, `ACTION_VENDOR_PROPOSED` tidak digunakan di manapun. Vendor confirmation flow sudah dihapus (migration `2026_01_22_000005`).

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus constants dari model. Biarkan labels di accessor untuk backward compatibility data historis.

---

## Temuan #20 — Slot Model: Status Inconsistencies

**Lokasi:** `app/Models/Slot.php`

**Masalah yang ditemukan:**

1. **`STATUS_REJECTED`** didefinisikan tapi tidak digunakan (migration sudah convert rejected → cancelled).
2. **`vendorActionStatuses()`** return empty array — dead method.
3. **`getStatusLabelAttribute()`** memiliki redundant `if ($this->status === 'rejected')` check sebelum match statement.
4. **`STATUS_ARRIVED`** dan **`STATUS_WAITING`** keduanya menampilkan label "Waiting" — dua status berbeda, satu label sama.

**Dampak:** UX confusion (user tidak bisa bedakan arrived vs waiting), dead code.

**Tingkat keparahan:** Medium

**Rekomendasi:**
- Berikan label berbeda: "Arrived" vs "Waiting".
- Hapus `STATUS_REJECTED`, `vendorActionStatuses()`, redundant if checks.

---

## Temuan #21 — late_reason Digunakan untuk Notes

**Lokasi:** `app/Services/BookingApprovalService.php` (baris 66)

**Masalah:** Field `late_reason` digunakan untuk menyimpan vendor booking notes:
```php
'late_reason' => $notes !== '' ? $notes : null,
```

**Penjelasan teknis:** Field `late_reason` secara semantik untuk alasan keterlambatan truck, bukan catatan vendor. Ini misuse field database.

**Dampak:** Data contamination — `late_reason` berisi data yang bukan alasan keterlambatan. Reporting berdasarkan field ini akan salah.

**Tingkat keparahan:** Medium

**Rekomendasi:** Tambahkan kolom `notes` ke tabel slots via migration. Gunakan `late_reason` hanya untuk keterlambatan.

---

## Temuan #22 — Legacy SapService dengan usleep()

**Lokasi:** `app/Services/SapService.php`

**Masalah:**
1. `usleep()` di demo mode — artificial delay 500-750ms per SAP call.
2. `env()` langsung di code — akan return null setelah `config:cache`.
3. Overlap fungsi dengan `SapPoService` dan `SapVendorService`.

**Dampak:** Performance loss, potential null config bug, maintenance confusion.

**Tingkat keparahan:** High

**Rekomendasi:** Migrate `env()` ke `config()`. Hapus `usleep()`. Consolidate ke `SapPoService`.

---

## Temuan #23 — SapPoService: Hardcoded Dummy Data

**Lokasi:** `app/Services/SapPoService.php` (baris 9-43)

**Masalah:** Array dummy purchase orders hardcoded di production code. Jika `SAP_PO_BASE_URL` kosong (misconfiguration), dummy data ditampilkan ke user seolah real.

**Dampak:** Data integrity risk di production.

**Tingkat keparahan:** Medium

**Rekomendasi:** Pindahkan dummy data ke seeder/fixture. Return empty array + warning log jika SAP tidak configured.

---

## Temuan #24 — DashboardController: Double Vendor Check

**Lokasi:** `app/Http/Controllers/DashboardController.php` (baris 30-51)

**Masalah:** Vendor role check dilakukan dua kali — direct DB query dan Spatie `hasRole()`.

**Dampak:** Extra DB query per dashboard access.

**Tingkat keparahan:** Low

**Rekomendasi:** Gunakan satu metode saja.

---

## Temuan #25 — Custom RoleMiddleware Bypass Spatie

**Lokasi:** `app/Http/Middleware/RoleMiddleware.php`

**Masalah:** Custom middleware yang bypass Spatie permission system dengan direct DB query, lalu fallback ke Spatie. Menunjukkan ketidakpercayaan terhadap package yang digunakan.

**Dampak:** Extra DB query per request pada routes dengan role middleware. Inconsistent behavior.

**Tingkat keparahan:** Medium

**Rekomendasi:** Fix root cause Spatie cache issue. Gunakan Spatie built-in middleware.

---

## Temuan #26 — 47 vite-hot-* Files di Storage

**Lokasi:** `storage/framework/vite-hot-*` (47 files)

**Masalah:** `DynamicBaseUrlMiddleware` membuat file per unique host+port, tidak pernah cleanup.

**Tingkat keparahan:** Low

**Rekomendasi:** Tambahkan cleanup mechanism. Hapus 47 file existing. Tambahkan ke `.gitignore`.

---

## Temuan #27 — View Composer '*' Performance

**Lokasi:** `app/Providers/AppServiceProvider.php` (baris 24-48)

**Masalah:** `View::composer('*', ...)` berjalan untuk **setiap** view render termasuk partials. Pada halaman dengan 20+ partials, ini 20+ function calls.

**Dampak:** Performance overhead per view render.

**Tingkat keparahan:** Medium

**Rekomendasi:** Ganti dengan `View::share('holidays', ...)` yang dipanggil sekali di `boot()`.

---

## Temuan #28 — User Model: name vs full_name Confusion

**Lokasi:** `app/Models/User.php` (baris 23-34, 58-67)

**Masalah:**
1. `name` dan `full_name` di `$fillable` — tapi `name` accessor redirects ke `full_name`.
2. `role` (string) di `$fillable` — legacy field, Spatie uses `role_id`.

**Dampak:** Developer confusion, potential data inconsistency.

**Tingkat keparahan:** Medium

**Rekomendasi:** Hapus `name` dan `role` dari `$fillable`.

---

## Temuan #29 — Gate Model: is_backup Not Cast

**Lokasi:** `app/Models/Gate.php`

**Masalah:** `is_active` di-cast ke boolean, `is_backup` tidak.

**Dampak:** Comparison `$gate->is_backup === true` bisa gagal.

**Tingkat keparahan:** Low

**Rekomendasi:** Tambahkan `'is_backup' => 'boolean'` ke `$casts`.

---

## Temuan #30 — Warehouse Model Missing gates() Relation

**Lokasi:** `app/Models/Warehouse.php`

**Masalah:** Gate has `belongsTo(Warehouse)` tapi Warehouse tidak punya inverse `hasMany(Gate)`.

**Tingkat keparahan:** Low

**Rekomendasi:** Tambahkan `gates()` relationship.
