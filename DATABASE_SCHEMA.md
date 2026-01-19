# Database Schema Documentation

## Overview
Aplikasi Slot Management menggunakan database SQLite dengan struktur tabel berikut:

## Tabel Utama

### 1. users
Menyimpan data pengguna sistem.
- `id` (INTEGER, Primary Key)
- `username` (varchar, unique) - Username untuk login
- `name` (varchar) - Nama lengkap pengguna
- `email` (varchar, unique) - Email pengguna
- `email_verified_at` (datetime, nullable)
- `password` (varchar) - Password hash
- `role` (varchar, default: 'user') - Role: admin, user
- `is_active` (boolean, default: true) - Status aktif
- `remember_token` (varchar, nullable)
- `created_at`, `updated_at` (datetime)

### 2. warehouses
Menyimpan data warehouse/gudang.
- `id` (INTEGER, Primary Key)
- `code` (varchar(10), unique) - Kode warehouse (WH1, WH2, dll)
- `name` (varchar) - Nama warehouse
- `is_active` (boolean, default: true) - Status aktif
- `created_at`, `updated_at` (datetime)

### 3. gates
Menyimpan data gate/pintu masuk di setiap warehouse.
- `id` (INTEGER, Primary Key)
- `warehouse_code` (varchar(10), Foreign Key) - Kode warehouse
- `gate_number` (integer) - Nomor gate
- `name` (varchar) - Nama gate (Gate 1A, dll)
- `is_active` (boolean, default: true) - Status aktif
- `created_at`, `updated_at` (datetime)
- Foreign Key: `warehouse_code` → `warehouses.code`
- Unique: (`warehouse_code`, `gate_number`)

### 4. vendors
Menyimpan data vendor dan customer.
- `id` (INTEGER, Primary Key)
- `code` (varchar(10), unique) - Kode vendor
- `name` (varchar) - Nama vendor/customer
- `type` (enum) - Tipe: vendor, customer
- `is_active` (boolean, default: true) - Status aktif
- `created_at`, `updated_at` (datetime)

### 5. slots
Tabel utama untuk menyimpan data slot waktu truck.
- `id` (INTEGER, Primary Key)
- `ticket_number` (varchar(50), unique) - Nomor ticket unik
- `po_number` (varchar(12), nullable) - Nomor PO/DO
- `mat_doc` (varchar(50), nullable) - Material document
- `sj_number` (varchar(50), nullable) - Surat jalan
- `truck_number` (varchar(20), nullable) - Nomor truck
- `truck_type` (varchar(100), nullable) - Jenis truck
- `direction` (varchar(20)) - Arah: inbound/outbound
- `warehouse_code` (varchar(10), Foreign Key) - Kode warehouse
- `gate_number` (integer, nullable) - Nomor gate
- `vendor_code` (varchar(10), Foreign Key, nullable) - Kode vendor
- `status` (enum, default: 'scheduled') - Status slot
  - scheduled: Terjadwal
  - arrived: Truck tiba
  - waiting: Menunggu proses
  - in_progress: Sedang proses
  - completed: Selesai
  - cancelled: Dibatalkan
- `slot_type` (enum, default: 'planned') - Tipe: planned/unplanned
- `planned_start`, `planned_finish` (timestamp, nullable) - Waktu terjadwal
- `actual_arrival`, `actual_start`, `actual_finish` (timestamp, nullable) - Waktu aktual
- `target_duration_minutes` (integer, nullable) - Durasi target (menit)
- `actual_duration_minutes` (integer, nullable) - Durasi aktual (menit)
- `lead_time_minutes` (integer, nullable) - Lead time (menit)
- `blocking_risk` (decimal(5,2), nullable) - Risiko blocking (%)
- `notes` (text, nullable) - Catatan tambahan
- `cancel_reason` (text, nullable) - Alasan pembatalan
- `created_by` (bigint, Foreign Key, nullable) - ID user yang buat
- `created_at`, `updated_at` (datetime)
- Index: (`warehouse_code`, `gate_number`, `planned_start`)
- Index: (`status`, `planned_start`)

### 6. po (Trucks)
Menyimpan data master PO/truck.
- `id` (INTEGER, Primary Key)
- `po_number` (varchar(12), unique) - Nomor PO
- `mat_doc` (varchar(50), nullable) - Material document
- `truck_number` (varchar(20)) - Nomor truck
- `truck_type` (varchar(100)) - Jenis truck
- `vendor_code` (varchar(10), Foreign Key) - Kode vendor
- `direction` (varchar(20)) - Arah: inbound/outbound
- `warehouse_code` (varchar(10), Foreign Key) - Kode warehouse
- `is_active` (boolean, default: true) - Status aktif
- `created_at`, `updated_at` (datetime)
- Index: (`po_number`, `mat_doc`)

### 7. truck_type_durations
Menyimpan durasi standar per jenis truck.
- `id` (INTEGER, Primary Key)
- `truck_type` (varchar(100), unique) - Jenis truck
- `target_duration_minutes` (integer) - Durasi target (menit)
- `created_at`, `updated_at` (datetime)

### 8. activity_logs
Menyimpan log aktivitas sistem.
- `id` (INTEGER, Primary Key)
- `type` (varchar(50)) - Tipe aktivitas (slot_created, slot_arrived, dll)
- `description` (text) - Deskripsi aktivitas
- `mat_doc` (varchar(50), nullable) - Material document terkait
- `po_number` (varchar(12), nullable) - PO terkait
- `slot_id` (bigint, Foreign Key, nullable) - ID slot terkait
- `user_id` (bigint, Foreign Key) - ID user yang melakukan
- `created_at`, `updated_at` (datetime)
- Index: (`type`, `created_at`)

## Relasi Antar Tabel
- `slots.warehouse_code` → `warehouses.code`
- `slots.vendor_code` → `vendors.code`
- `slots.created_by` → `users.id`
- `gates.warehouse_code` → `warehouses.code`
- `po.vendor_code` → `vendors.code`
- `po.warehouse_code` → `warehouses.code`
- `activity_logs.slot_id` → `slots.id`
- `activity_logs.user_id` → `users.id`

## Data Awal (Seeders)
- **Admin User**: username: `admin`, password: `password`
- **Warehouses**: WH1, WH2, WH3
- **Gates**: 
  - WH1: Gate 1A, 1B
  - WH2: Gate 2A, 2B, 2C
  - WH3: Gate 3A, 3B
- **Vendors**: V001-V005 (vendor), C001-C002 (customer)
