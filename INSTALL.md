# 📋 Panduan Instalasi Sistem Absensi Karyawan

## 🚀 Langkah-langkah Instalasi

### 1. Persiapan Environment
- Pastikan XAMPP sudah terinstall dan berjalan
- Apache dan MySQL harus aktif
- PHP versi 7.4 atau lebih tinggi

### 2. Setup Database
1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Buat database baru dengan nama `db_karyawan`
3. Import file `setup_database.sql` ke database tersebut
4. Pastikan tabel `tb_karyawan` dan `tb_absensi` sudah terbentuk

### 3. Konfigurasi Aplikasi
1. Copy semua file ke folder `C:\xampp\htdocs\project_telkom`
2. Pastikan file `config.php` memiliki kredensial database yang benar:
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db   = "db_karyawan";
   ```

### 4. Testing Aplikasi
1. Buka browser dan akses: `http://localhost/project_telkom`
2. Anda akan diarahkan ke halaman demo
3. Klik "Mulai Demo" untuk masuk ke halaman login
4. Gunakan data login berikut:
   - NIK: `1234567890123456`
   - Password: `123456`

## 🔧 Troubleshooting

### Database Connection Error
- Pastikan MySQL service berjalan di XAMPP
- Cek kredensial database di `config.php`
- Pastikan database `db_karyawan` sudah dibuat

### Page Not Found Error
- Pastikan file berada di folder `htdocs`
- Cek URL yang digunakan
- Pastikan Apache service berjalan

### Login Error
- Pastikan database sudah diimport dengan benar
- Cek data karyawan di tabel `tb_karyawan`
- Pastikan password menggunakan hash yang benar

## 📁 Struktur File

```
project_telkom/
├── index.php          # Halaman utama (redirect)
├── demo.html          # Halaman demo
├── login.php          # Halaman login
├── dashboard.php      # Dashboard absensi
├── absen.php          # Proses absensi
├── riwayat_absen.php  # Riwayat absensi
├── logout.php         # Logout
├── config.php         # Konfigurasi database
├── koneksi.php        # Koneksi database
├── style.css          # Styling CSS
├── telkom.png         # Logo Telkom
├── setup_database.sql # Script database
├── README.md          # Dokumentasi
└── INSTALL.md         # Panduan instalasi
```

## 🎯 Fitur yang Tersedia

- ✅ Login dengan NIK dan password
- ✅ Check In dan Check Out
- ✅ Dashboard dengan informasi karyawan
- ✅ Riwayat absensi 30 hari terakhir
- ✅ Statistik kehadiran
- ✅ Jam real-time
- ✅ Design responsif
- ✅ Warna tema Telkom

## 📞 Support

Jika mengalami masalah, silakan cek:
1. Log error di browser (F12)
2. Log error di XAMPP
3. Pastikan semua file sudah ter-copy dengan benar
4. Pastikan database sudah diimport

## 🔄 Update

Untuk update sistem:
1. Backup database terlebih dahulu
2. Replace file yang diupdate
3. Jalankan script SQL jika ada perubahan struktur database
4. Test semua fitur

---

**Selamat menggunakan Sistem Absensi Karyawan TelkomAkses! 🎉**
