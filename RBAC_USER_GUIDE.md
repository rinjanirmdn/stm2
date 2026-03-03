# RBAC User Guide (Create / Edit / View-Only)

## 1) Purpose
Dokumen ini menjelaskan:
- Struktur **Role-Based Access Control (RBAC)** pada aplikasi.
- Level akses **View-Only**, **Create**, dan **Edit**.
- User flow per role.
- Negative cases dan standar error handling.

---

## 2) Access Levels

### A. View-Only
- Dapat melihat daftar/detail data.
- Tidak dapat membuat/mengubah/menghapus.
- Permission pattern utama:
  - `*.view`, `*.index`, `*.show`, `*.search_suggestions`, `*.api_index`, `*.stream`

### B. Create
- Mewarisi akses View-Only.
- Dapat membuat data baru.
- Permission pattern tambahan:
  - `*.create`, `*.store`

### C. Edit
- Mewarisi akses View-Only.
- Dapat mengubah data existing.
- Permission pattern tambahan:
  - `*.edit`, `*.update`

> Catatan: aksi sensitif (approve/reject/delete/toggle/cancel/start/complete) tetap memakai permission spesifik, tidak otomatis terbuka hanya karena role Create/Edit.

---

## 3) Roles

## Core Roles
- **Admin**: full access (semua permission).
- **Section Head**: mayoritas operasional, tanpa user management/logs.
- **Operator**: operasional terbatas (process flow lapangan).
- **Vendor**: akses portal vendor (role-based route).

## Mapping level akses pada master roles
- **Viewer**: view-only lintas modul.
- **Operator**: dominan view + aksi proses operasional (arrival/start/complete), bukan full edit master data.
- **Section Head**: mayoritas create/edit operasional (kecuali user management/logs).
- **Admin / Super Admin / Super Account**: full access.

Level akses create/edit/view-only tetap dipetakan lewat permission set pada role master yang sudah ada di `md_roles`.

---

## 4) Module-to-Permission Reference (Ringkas)

- Dashboard:
  - view: `dashboard.view`
  - filter lanjutan: `dashboard.range_filter`

- Slots (planned):
  - view: `slots.index`, `slots.show`, `slots.search_suggestions`
  - create: `slots.create`, `slots.store`
  - edit: `slots.edit`, `slots.update`
  - actions: `slots.arrival*`, `slots.start*`, `slots.complete*`, `slots.cancel*`, `slots.ticket`

- Unplanned:
  - view: `unplanned.index`
  - create: `unplanned.create`, `unplanned.store`
  - edit: `unplanned.edit`, `unplanned.update`
  - actions: mengikuti endpoint operasional unplanned yang saat ini diproteksi permission existing (`unplanned.index` untuk halaman aksi, `unplanned.update` untuk submit aksi)

- Reports:
  - `reports.transactions`, `reports.search_suggestions`, `reports.export`, `reports.gate_status`

- Gates:
  - view: `gates.index`, `gates.api_index`, `gates.stream`
  - action: `gates.toggle`

- Users:
  - view: `users.index`
  - create: `users.create`, `users.store`
  - edit: `users.edit`, `users.update`
  - action: `users.delete`, `users.toggle`

- Trucks:
  - view: `trucks.index`
  - create: `trucks.create`, `trucks.store`
  - edit: `trucks.edit`, `trucks.update`
  - action: `trucks.delete`

---

## 5) User Flow per Role

## Admin
1. Login.
2. Akses semua modul dari sidebar/top navigation.
3. Dapat melakukan create/edit/approve/reject/delete/toggle.
4. Jika API/AJAX dipanggil, response sukses standar `success: true`.

## Viewer
1. Login.
2. Akses halaman list/detail yang diizinkan.
3. Tombol aksi mutasi data tidak tersedia/ditolak oleh backend.
4. Jika memaksa endpoint mutasi -> 403 Forbidden.

## Operator
1. Login.
2. Akses list/detail operasional yang diizinkan.
3. Dapat menjalankan flow proses (`arrival/start/complete`) sesuai permission.
4. Jika memaksa akses create/edit yang tidak diizinkan -> 403 Forbidden.

## Section Head
1. Login.
2. Akses mayoritas modul operasional.
3. Dapat create/edit sesuai permission role.
4. Tidak dapat akses area yang dikecualikan (contoh users/logs) jika tidak diberi permission.

## Vendor
1. Login sebagai role vendor.
2. Akses route `/vendor/*` sesuai flow booking vendor.
3. Jika akses route admin -> ditolak middleware role/permission.

---

## 6) Negative Cases + Expected Handling

## A. Unauthorized Access (403)
**Case:** User tanpa permission mengakses route/aksi.
- Web: tampil halaman 403 (default Laravel/Spatie).
- AJAX/JSON: 
```json
{
  "success": false,
  "code": "FORBIDDEN",
  "message": "You are not authorized to perform this action."
}
```

## B. Validation Error (422)
**Case:** Payload invalid / field wajib kosong / format salah.
- AJAX/JSON:
```json
{
  "success": false,
  "code": "VALIDATION_ERROR",
  "message": "Validation failed.",
  "errors": {
    "field": ["..."]
  }
}
```

## C. Not Found (404)
**Case:** Resource tidak ditemukan (`/id` salah atau sudah dihapus).
- AJAX/JSON:
```json
{
  "success": false,
  "code": "NOT_FOUND",
  "message": "Requested resource was not found."
}
```

## D. Session Expired / Not Authenticated
**Case:** user belum login / session timeout.
- Web: redirect ke login.
- AJAX: ikuti redirect/auth middleware (frontend perlu handle `res.status===401/302`).

---

## 7) Operational Checklist (Go-Live)

1. Jalankan seeder RBAC terbaru:
```bash
php artisan db:seed --class=RolePermissionSeeder
```

2. Assign role user sesuai matriks organisasi dan role master (`Admin`, `Section Head`, `Operator`, `Super Admin`, `Viewer`, `Vendor`, `Security`, `Super Account`).
3. Uji smoke test minimal:
   - Viewer tidak bisa POST create/edit/delete.
   - Operator hanya bisa aksi proses operasional yang diizinkan.
   - Section Head bisa create/edit sesuai scope yang ditetapkan.
   - Admin bisa semua.
4. Uji endpoint AJAX unauthorized/validation/not-found.
5. Pastikan menu UI menyembunyikan aksi yang tidak diizinkan (`@can` / permission checks).

---

## 8) Notes for Product/QA
- Role matrix dapat diperluas per modul (contoh: `bookings.approve` khusus approver).
- Untuk audit, simpan log aktivitas untuk aksi sensitif (approve/reject/delete/toggle).
- Untuk perubahan role policy, update seeder + regression test akses route utama.
