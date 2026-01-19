# Network Access Guide - Slot Time Management

## Cara Mengakses Website dari Perangkat Lain

### 1. Dapatkan IP Address Komputer Anda

**Windows:**
```bash
# Buka Command Prompt/PowerShell
ipconfig
# Cari "IPv4 Address" biasanya 192.168.x.x
```

**Mac/Linux:**
```bash
# Buka Terminal
ifconfig | grep "inet " | grep -v 127.0.0.1
# Atau
hostname -I
```

### 2. Update Konfigurasi Laravel

Edit file `.env`:
```env
APP_URL=http://192.168.1.100:8000
```
(Ganti dengan IP address Anda)

### 3. Start Development Server dengan Host Binding

**Option A: Start dengan IP spesifik**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Option B: Start dengan IP address Anda**
```bash
php artisan serve --host=192.168.1.100 --port=8000
```

### 4. Akses dari Perangkat Lain

**Dari HP/Tablet/Laptop lain:**
1. Pastikan terhubung ke WiFi yang sama
2. Buka browser
3. Akses: `http://192.168.1.100:8000`

### 5. Troubleshooting

#### Firewall Windows
```bash
# Allow port 8000 di Windows Firewall
netsh advfirewall firewall add rule name="Laravel" dir=in action=allow protocol=TCP localport=8000
```

#### XAMPP Configuration
Jika menggunakan XAMPP, edit `httpd-xampp.conf`:
```apache
<Directory "C:/xampp/htdocs">
    AllowOverride All
    Require all granted
</Directory>
```

#### Check Koneksi
```bash
# Test dari komputer server
curl http://192.168.1.100:8000

# Test dari perangkat lain
ping 192.168.1.100
```

### 6. Mobile Testing Tips

#### Chrome DevTools Mobile
1. F12 → Toggle device toolbar
2. Select device model
3. Add custom device jika perlu

#### Real Device Testing
1. Enable USB debugging (Android)
2. Chrome://inspect untuk remote debugging

### 7. PWA Testing di Mobile

Setelah bisa diakses:
1. Buka di Chrome mobile
2. Klik menu (3 dots) → "Add to Home screen"
3. Test offline mode:
   - Install sebagai app
   - Matikan WiFi
   - Buka dari home screen

### 8. QR Code Testing

Generate QR untuk mobile access:
```bash
# Install qrcode generator
npm install -g qrcode-terminal

# Generate QR
qrcode-terminal "http://192.168.1.100:8000"
```

### 9. Network Performance

#### Test Loading Speed
```bash
# Di Chrome DevTools
# Network tab → Throttling → Slow 3G
```

#### Optimize untuk Mobile
- Compress images
- Enable gzip
- Minify CSS/JS

### 10. Security Notes

⚠️ **Development Mode Only**
- Jangan gunakan di production
- APP_DEBUG=true tidak aman
- Hanya untuk network lokal

### 11. Common Issues

#### "Connection Refused"
- Pastikan server running
- Check firewall
- Verify IP address

#### "404 Not Found"
- Check .htaccess
- Verify APP_URL
- Clear cache: `php artisan cache:clear`

#### "Access Denied"
- Check folder permissions
- XAMPP security settings

### 12. Production Deployment Preview

Untuk production access:
1. Gunakan domain/subdomain
2. SSL certificate
3. Production server (Apache/Nginx)
4. Environment variables: APP_ENV=production

---

## Quick Start Commands

```bash
# 1. Cari IP Anda
ipconfig

# 2. Update .env
APP_URL=http://[IP_ANDA]:8000

# 3. Clear cache
php artisan config:clear
php artisan cache:clear

# 4. Start server
php artisan serve --host=0.0.0.0 --port=8000

# 5. Test dari HP
http://[IP_ANDA]:8000
```

## Testing Checklist

- [ ] Website bisa diakses dari laptop lain
- [ ] Responsive di mobile browser
- [ ] PWA install works
- [ ] QR code check-in functional
- [ ] Real-time updates work
- [ ] Performance acceptable

---

**Note:** Ganti `192.168.1.100` dengan IP address komputer Anda yang sebenarnya.
