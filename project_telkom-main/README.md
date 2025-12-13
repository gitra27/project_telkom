# 🏢 Sistem Absensi Karyawan TelkomAkses

Sistem absensi karyawan modern yang menggunakan NIK dan password untuk login dengan tampilan yang profesional dan user-friendly.

## ✨ Fitur Utama

### 🔐 Autentikasi & Keamanan
- Login menggunakan NIK dan password
- Password terenkripsi dengan `password_hash()`
- Session management yang aman
- Redirect otomatis jika belum login

### ⏰ Sistem Absensi
- Check In dan Check Out harian
- Jam real-time yang update otomatis
- Validasi absensi harian
- Status absensi yang jelas (Hadir, Terlambat, Tidak Hadir)

### 📊 Dashboard & Laporan
- Dashboard dengan informasi karyawan
- Riwayat absensi 30 hari terakhir
- Statistik kehadiran (Total Hari, Hari Lengkap, Belum Pulang, Tidak Hadir)
- Perhitungan durasi kerja otomatis
- Export data (coming soon)

### 🎨 Interface & UX
- Design modern dan responsif
- Warna tema merah Telkom
- Animasi dan transisi yang smooth
- Mobile-friendly
- Font Awesome icons
- Bootstrap 5 framework

## 🚀 Instalasi

### Prasyarat
- XAMPP (Apache + MySQL + PHP 7.4+)
- Web browser modern
- phpMyAdmin

### Langkah Instalasi

1. **Download & Extract**
   ```bash
   # Clone atau download project ke folder htdocs
   C:\xampp\htdocs\project_telkom
   ```

2. **Setup Database**
   - Buka phpMyAdmin: `http://localhost/phpmyadmin`
   - Buat database baru: `db_karyawan`
   - Import file `setup_database.sql`

3. **Konfigurasi Database**
   - Edit file `config.php` jika diperlukan
   - Pastikan kredensial database sesuai dengan XAMPP

4. **Akses Aplikasi**
   - Buka browser: `http://localhost/project_telkom`
   - Login dengan data contoh di bawah

## 👥 Data Login Contoh

| NIK | Password | Nama | Jabatan | Departemen |
|-----|----------|------|---------|------------|
| 1234567890123456 | 123456 | Ahmad Wijaya | Manager | IT |
| 2345678901234567 | 123456 | Siti Nurhaliza | Staff | HR |
| 3456789012345678 | 123456 | Budi Santoso | Supervisor | Finance |
| 4567890123456789 | 123456 | Dewi Kartika | Staff | Marketing |
| 5678901234567890 | 123456 | Rizki Pratama | Staff | IT |

## Struktur Database

### Tabel tb_karyawan
- `id`: Primary key
- `nik`: NIK karyawan (unique)
- `nama`: Nama karyawan
- `password`: Password terenkripsi
- `jabatan`: Jabatan karyawan
- `departemen`: Departemen karyawan

### Tabel tb_absensi
- `id`: Primary key
- `nik`: NIK karyawan (foreign key)
- `tanggal`: Tanggal absensi
- `jam_masuk`: Jam masuk
- `jam_pulang`: Jam pulang
- `status`: Status absensi

## File Utama

- `index.php`: Halaman utama absensi
- `login.php`: Halaman login
- `absen.php`: Proses absensi
- `riwayat_absen.php`: Riwayat absensi
- `logout.php`: Logout
- `config.php`: Konfigurasi database
- `style.css`: Styling CSS

## Cara Penggunaan

1. Buka website di browser
2. Login menggunakan NIK dan password
3. Klik "Check In" untuk absen masuk
4. Klik "Check Out" untuk absen pulang
5. Lihat riwayat absensi di menu "Riwayat Absen"
6. Logout setelah selesai

## Keamanan

- Password dienkripsi menggunakan `password_hash()`
- Session management untuk autentikasi
- Input validation untuk mencegah SQL injection
- Redirect otomatis jika belum login

## Customization

Warna tema dapat diubah di file `style.css` pada bagian:
- `.btn-primary`: Warna tombol utama
- `.btn-success`: Warna tombol Check In
- `.btn-danger`: Warna tombol Check Out
- `.btn-secondary`: Warna tombol lainnya
