# Vendor Transporter - System Documentation

---

## ENGLISH VERSION

### Vendor Transporter List

The Vendor Transporter List page displays all registered transport companies in the e-DCS system.
This module serves to categorize and distinguish between rental trucks (Sewa) and OJI-owned trucks during internal booking creation for Outbound operations.
Columns: No, Transporter Name, Status (Active/Inactive with color-coded badges).
Search by transporter name.
Filter by status (All, Active, Inactive).
Pagination with configurable page size (10, 25, 50, 100).
"Add" button to register a new transporter.
Actions: Edit and Delete per transporter with confirmation dialog.
Delete protection: Cannot delete transporters currently in use by slots.

### Create Transporter

New transporter registration form.
Fields: Transporter Name (required, unique, max 255 characters), Active status (checkbox, default checked).
Validation: Name must be unique across all transporters.
Save button to persist data and return to list.
Cancel button to return without saving.

### Edit Transporter

Transporter edit form. Allows the administrator to modify transporter information, such as name and active status.
Fields: Transporter Name (required, unique excluding current record), Active status (checkbox).
Save Changes button to update data and return to list.
Cancel button to return without saving changes.

### Delete Transporter

Transporter deletion feature. Confirmation is required before deletion.
System checks if transporter is referenced in slots table.
If in use, deletion is blocked with error message.
If not in use, transporter is deleted successfully.

---

## BAHASA INDONESIA

### Daftar Vendor Transporter

Halaman Daftar Vendor Transporter menampilkan semua perusahaan transportir yang terdaftar dalam sistem e-DCS.
Modul ini berfungsi untuk mengkategorikan dan membedakan antara truk sewa dan truk milik OJI saat pembuatan booking internal untuk operasi Outbound.
Kolom: No, Nama Transporter, Status (Active/Inactive dengan badge berwarna).
Pencarian berdasarkan nama transportir.
Filter berdasarkan status (All, Active, Inactive).
Pagination dengan ukuran halaman yang dapat dikonfigurasi (10, 25, 50, 100).
Tombol "Add" untuk mendaftarkan transportir baru.
Aksi: Edit dan Delete per transportir dengan dialog konfirmasi.
Proteksi delete: Tidak dapat menghapus transportir yang sedang digunakan oleh slots.

### Buat Transportir

Form pendaftaran transportir baru.
Field: Nama Transporter (wajib, unik, maksimal 255 karakter), Status Active (checkbox, default dicentang).
Validasi: Nama harus unik di seluruh transportir.
Tombol Save untuk menyimpan data dan kembali ke daftar.
Tombol Cancel untuk kembali tanpa menyimpan.

### Edit Transportir

Form edit transportir. Memungkinkan administrator mengubah informasi transportir, seperti nama dan status aktif.
Field: Nama Transporter (wajib, unik mengecualikan record saat ini), Status Active (checkbox).
Tombol Save Changes untuk mengupdate data dan kembali ke daftar.
Tombol Cancel untuk kembali tanpa menyimpan perubahan.

### Hapus Transportir

Fitur penghapusan transportir. Konfirmasi diperlukan sebelum penghapusan.
Sistem memeriksa apakah transportir direferensikan dalam tabel slots.
Jika sedang digunakan, penghapusan diblokir dengan pesan error.
Jika tidak digunakan, transportir berhasil dihapus.
