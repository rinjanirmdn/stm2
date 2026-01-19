# Manual Test Cases - Slot Time Management

## Preparation
- Login dengan akun admin atau user
- Pastikan database sudah terisi data dummy (seeder)

## 1. Core Slot Management

### 1.1 Create Planned Slot
**Steps:**
1. Buka menu Slots → Create Planned Slot
2. Isi form:
   - PO Number: TEST001
   - Truck Number: TRUCK-001
   - Truck Type: Container 20ft
   - Direction: Inbound
   - Vendor: Pilih vendor
   - Warehouse: WH1
   - Planned Gate: Pilih gate
   - Planned Start: Pilih tanggal & waktu
   - Planned Duration: 60 (menit)
3. Klik Save

**Expected Result:**
- Slot berhasil dibuat
- Muncul di daftar slots
- Status: scheduled

### 1.2 Slot Progression Flow
**Steps:**
1. Buka slot yang baru dibuat
2. Klik tombol "Arrival"
3. Isi arrival time (gunakan waktu saat ini)
4. Save
5. Klik tombol "Start Slot"
6. Klik tombol "Complete Slot"

**Expected Result:**
- Status berubah: scheduled → arrived → in_progress → completed
- Activity log tercatat untuk setiap aksi

### 1.3 Edit Slot
**Steps:**
1. Buka slot dengan status "scheduled"
2. Klik tombol Edit
3. Ubah planned gate atau waktu
4. Save

**Expected Result:**
- Data berhasil diupdate
- Tidak ada error

## 2. Search & Filter

### 2.1 Search by PO Number
**Steps:**
1. Di halaman Slots, ketik PO number di search box
2. Tekan Enter

**Expected Result:**
- Menampilkan slot yang sesuai
- AJAX suggestions muncul saat mengetik

### 2.2 Filter by Date Range
**Steps:**
1. Pilih tanggal di filter "Planned Start"
2. Klik Apply Filter

**Expected Result:**
- Hanya slot dalam range tanggal yang ditampilkan

### 2.3 Filter by Status
**Steps:**
1. Pilih status di dropdown filter
2. Klik Apply Filter

**Expected Result:**
- Filter berfungsi dengan benar

## 3. Real-time Gate Status

### 3.1 View Live Gate Status
**Steps:**
1. Buka menu Reports → Gate Status
2. Perhatikan status gate

**Expected Result:**
- Status real-time ditampilkan
- Connection status indikator hijau (connected)

### 3.2 Test Auto-refresh
**Steps:**
1. Buka slot baru di tab lain
2. Ubah status slot
3. Perhatikan gate status dashboard

**Expected Result:**
- Status terupdate otomatis dalam 2 detik

## 4. QR Code Check-in

### 4.1 Generate QR Code
**Steps:**
1. Buka detail slot
2. Klik tombol "QR Check-in"

**Expected Result:**
- QR code tergenerate
- Mobile-friendly page terbuka di tab baru

### 4.2 Mobile Check-in Flow
**Steps:**
1. Scan QR code dengan phone camera
2. Klik link yang muncul
3. Di mobile check-in page:
   - Verifikasi informasi slot
   - Klik "Check-in Arrival"
4. Refresh halaman utama

**Expected Result:**
- Status slot berubah
- Activity log tercatat
- Mobile UI responsif

## 5. PWA Features

### 5.1 Install as App
**Steps:**
1. Buka aplikasi di Chrome/Edge
2. Klik icon download (+) di address bar
3. Klik "Install"

**Expected Result:**
- Aplikasi terinstall di desktop/start menu
- Buka tanpa browser UI

### 5.2 Offline Access
**Steps:**
1. Install aplikasi sebagai PWA
2. Buka beberapa halaman
3. Matikan internet
4. Reload halaman

**Expected Result:**
- Halaman masih bisa diakses (cached)
- Tampil "Offline mode" di service worker

## 6. SAP Integration (Demo Mode)

### 6.1 PO Search
**Steps:**
1. Buka Postman atau curl
2. Kirim request:
   ```
   POST /api/sap/po/search
   {
     "po_number": "PO123456",
     "vendor_code": "V1001"
   }
   ```

**Expected Result:**
- Response 200 dengan data PO demo
- Log "[DEMO] SAP PO Search" di Laravel log

### 6.2 PO Details
**Steps:**
1. Kirim request:
   ```
   GET /api/sap/po/PO123456
   ```

**Expected Result:**
- Response 200 dengan detail PO
- Include items, vendor, total value

### 6.3 Health Check
**Steps:**
1. Kirim request:
   ```
   GET /api/sap/health
   ```

**Expected Result:**
- Status: "demo_mode"
- Message mengandung "demo"

## 7. Reports

### 7.1 Transaction Report
**Steps:**
1. Buka Reports → Transactions
2. Pilih date range
3. Klik Generate

**Expected Result:**
- Laporan transaksi muncul
- Bisa diexport ke PDF/Excel

### 7.2 Gate Status Report
**Steps:**
1. Buka Reports → Gate Status
2. Pilih warehouse
3. Klik Generate

**Expected Result:**
- Summary gate status
- Filter berfungsi

## 8. Performance Tests

### 8.1 Large Dataset
**Steps:**
1. Buat 100+ slot via seeder
2. Buka halaman slots
3. Test pagination (10, 25, 50, 100)

**Expected Result:**
- Loading time < 3 detik
- Pagination smooth

### 8.2 Concurrent Users
**Steps:**
1. Buka 5+ browser dengan user berbeda
2. Edit slot bersamaan
3. Monitor performance

**Expected Result:**
- Tidak ada deadlock
- Update berhasil

## 9. Security Tests

### 9.1 Role-based Access
**Steps:**
1. Login sebagai user biasa (bukan admin)
2. Akses menu admin (vendors, users)

**Expected Result:**
- Access denied (403)
- Tidak bisa melihat menu admin

### 9.2 Input Validation
**Steps:**
1. Create slot dengan input invalid:
   - PO number kosong
   - Tanggal di masa lalu
   - Duration negatif

**Expected Result:**
- Validation error message
- Tidak ada data tersimpan

## 10. Mobile Responsiveness

### 10.1 Mobile View
**Steps:**
1. Buka di mobile device atau Chrome DevTools (mobile view)
2. Test semua halaman

**Expected Result:**
- Layout responsif
- Touch-friendly buttons
- No horizontal scroll

### 10.2 Mobile Performance
**Steps:**
1. Test di 3G network simulation
2. Load halaman slots

**Expected Result:**
- Load time < 5 detik
- Images optimized

## 11. Error Handling

### 11.1 Network Error
**Steps:**
1. Matikan server mid-request
2. Coba save slot

**Expected Result:**
- Error message user-friendly
- Tidak crash aplikasi

### 11.2 Server Error (500)
**Steps:**
1. Force error di controller
2. Refresh halaman

**Expected Result:**
- Error page 500 yang bagus
- Log error tercatat

## 12. Data Integrity

### 12.1 Concurrent Updates
**Steps:**
1. Buka slot yang sama di 2 tab
2. Edit data berbeda
3. Save bersamaan

**Expected Result:**
- Data konsisten
- Tidak corrupt

### 12.2 Foreign Key Constraints
**Steps:**
1. Coba hapus warehouse yang digunakan slot
2. Coba hapus vendor yang digunakan slot

**Expected Result:**
- Constraint error
- Data tidak terhapus

---

## Test Completion Checklist

- [ ] Semua test cases di atas dieksekusi
- [ ] Screenshot hasil test disimpan
- [ ] Bugs dicatat di issue tracker
- [ ] Performance metrics dicatat
- [ ] Test results didokumentasikan

## Notes

- Gunakan data dummy yang realistis
- Test di multiple browsers (Chrome, Firefox, Edge)
- Test di mobile device jika memungkinkan
- Document semua anomali atau edge cases
