# Technical Forensic Audit Report - Part 1
## e-Docking Control System (STM2)
**Date:** 2026-03-04

---

## Temuan #1 — Kredensial Sensitif di .env

**Lokasi:** `.env` (baris 31, 46, 62)

**Masalah:** File `.env` berisi kredensial sensitif: `DB_PASSWORD=admin123`, `SAP_PO_PASSWORD=@DragonForce.7`, `MAIL_PASSWORD=xrbdasbqgamucdiy`

**Penjelasan teknis:** Password DB sangat lemah. SAP dan Gmail credentials terekspos. Siapapun dengan akses repo bisa akses semua system.

**Dampak:** Kebocoran akses database, SAP API, dan email admin.

**Tingkat keparahan:** Critical

**Rekomendasi:** Rotasi semua password. Gunakan password kuat (16+ karakter). Gunakan secret management untuk production.

---

## Temuan #2 — File .env.backup Corrupt

**Lokasi:** `.env.backup`

**Masalah:** File berisi null bytes (corrupt), tidak bisa dibaca.

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus file ini.

---

## Temuan #3 — test_mail.php di Project Root

**Lokasi:** `test_mail.php`

**Masalah:** Script test yang hardcode user ID 27, mem-bootstrap Laravel, dan mengirim email. Bisa diakses via HTTP jika web server serve dari root.

**Dampak:** Security risk (trigger email tanpa auth), information disclosure (ekspos data user).

**Tingkat keparahan:** High

**Rekomendasi:** Hapus file. Gunakan Artisan command untuk testing.

---

## Temuan #4 — Duplicate Route slots.show

**Lokasi:** `routes/web.php` (baris 72-75)

**Masalah:** Route `slots.show` didefinisikan dua kali identik.

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus baris duplikat (74-75).

---

## Temuan #5 — Route gates.toggle Override Bug

**Lokasi:** `routes/web.php` (baris 216-222)

**Masalah:** Route `gates.toggle` didefinisikan dua kali — operator route di-override oleh admin route. Operator **tidak bisa** toggle gate.

**Penjelasan teknis:** Laravel menggunakan route terakhir yang terdaftar. Route operator (baris 217) di-override oleh route admin (baris 221) karena nama dan path identik.

**Dampak:** Bug fungsional — operator kehilangan akses toggle gate.

**Tingkat keparahan:** High

**Rekomendasi:** Gabungkan menjadi satu route dengan logic authorization di controller/policy.

---

## Temuan #6 — Duplicate Approve/Reject Routes

**Lokasi:** `routes/web.php` (baris 97-101 dan 291-294)

**Masalah:** Route approve/reject booking ada di dua prefix: `slots` dan `bookings`, keduanya ke `BookingApprovalController`.

**Dampak:** Kebingungan API, middleware berbeda antar endpoint.

**Tingkat keparahan:** Medium

**Rekomendasi:** Hapus duplikat di prefix `slots`.

---

## Temuan #7 — Dead Code: SlotResource.php

**Lokasi:** `app/Http/Resources/SlotResource.php`

**Masalah:** Tidak digunakan di manapun dalam codebase.

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus file.

---

## Temuan #8 — Dead Code: SlotDTO.php

**Lokasi:** `app/DTOs/SlotDTO.php` (230 baris)

**Masalah:** Tidak digunakan di manapun. Field mapping tidak sesuai current schema (`truck_number`, `planned_finish`, `cancel_reason`, dll tidak ada di tabel slots).

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus file.

---

## Temuan #9 — Dead Code: SlotRepository.php

**Lokasi:** `app/Repositories/SlotRepository.php` (11,983 bytes)

**Masalah:** Tidak digunakan di manapun. Remnant dari arsitektur awal.

**Tingkat keparahan:** Medium

**Rekomendasi:** Hapus file dan folder `Repositories/`.

---

## Temuan #10 — vendor-dashboard.js Tidak di Vite Build

**Lokasi:** `resources/js/vendor-dashboard.js`

**Masalah:** File tidak terdaftar di `vite.config.js` input. Hanya direferensi di vendor dashboard blade tapi tidak di-bundle.

**Dampak:** Fitur JS vendor dashboard mungkin tidak berfungsi di production build.

**Tingkat keparahan:** Medium

**Rekomendasi:** Tambahkan ke `vite.config.js` atau hapus jika tidak diperlukan.

---

## Temuan #11 — Dead React Components

**Lokasi:** `resources/js/react/vendor-ontime-chart.jsx`, `vendor-status-overview.jsx`

**Masalah:** Tidak di-import, tidak di-render, tidak di Vite config.

**Tingkat keparahan:** Low

**Rekomendasi:** Hapus kedua file.

---

## Temuan #12 — Echo/Reverb Broadcasting Tidak Digunakan

**Lokasi:** `resources/js/echo.js`, `bootstrap.js`, `app.js`

**Masalah:** Laravel Echo + Pusher diinisialisasi setiap page load, tapi tidak ada channel subscription. `channels.php` kosong. Reverb mungkin tidak berjalan.

**Dampak:** Unnecessary WebSocket connection attempts, bundle size membengkak (~50KB pusher-js).

**Tingkat keparahan:** Medium

**Rekomendasi:** Hapus echo.js import jika broadcasting tidak digunakan. Hapus `laravel/reverb` dan `pusher-js` dependencies.

---

## Temuan #13 — CSS Monolith: style.css 201KB

**Lokasi:** `resources/css/style.css` (201,552 bytes)

**Masalah:** File CSS sangat besar, render-blocking, kemungkinan banyak dead CSS.

**Dampak:** Performance: slow page load. Maintainability: sulit mengelola.

**Tingkat keparahan:** High

**Rekomendasi:** Audit dan hapus unused CSS. Lanjutkan split per page/feature. Target < 50KB.

---

## Temuan #14 — vendor.css 82KB

**Lokasi:** `resources/css/vendor.css` (82,516 bytes)

**Masalah:** Sangat besar untuk portal vendor dengan ~6 halaman.

**Tingkat keparahan:** Medium

**Rekomendasi:** Audit dan hapus unused CSS.

---

## Temuan #15 — SlotController.php 2,176 Baris

**Lokasi:** `app/Http/Controllers/SlotController.php` (92KB)

**Masalah:** Controller dengan 30+ public methods, melanggar SRP. Menangani slots, unplanned, arrival, start, complete, cancel, ticket, export, semua AJAX.

**Dampak:** Sangat sulit di-maintain, di-test, di-review.

**Tingkat keparahan:** High

**Rekomendasi:** Split ke `UnplannedSlotController`, `SlotAjaxController`. Hapus private wrapper methods.
