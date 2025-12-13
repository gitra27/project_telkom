# 🤝 Contributing to Sistem Absensi Karyawan TelkomAkses

Terima kasih atas minat Anda untuk berkontribusi pada proyek ini! Kami sangat menghargai kontribusi dari komunitas.

## 📋 Cara Berkontribusi

### 1. Fork Repository
1. Fork repository ini ke akun GitHub Anda
2. Clone repository yang sudah di-fork
3. Buat branch baru untuk fitur atau perbaikan

### 2. Setup Development Environment
1. Install XAMPP atau environment PHP/MySQL
2. Setup database menggunakan `setup_database.sql`
3. Konfigurasi `config.php` sesuai environment Anda
4. Test aplikasi untuk memastikan berjalan dengan baik

### 3. Guidelines untuk Kontribusi

#### 🐛 Bug Reports
- Gunakan template issue yang tersedia
- Jelaskan langkah-langkah untuk reproduce bug
- Sertakan screenshot jika diperlukan
- Jelaskan environment yang digunakan

#### ✨ Feature Requests
- Jelaskan fitur yang ingin ditambahkan
- Berikan use case yang jelas
- Jelaskan manfaat fitur tersebut
- Sertakan mockup atau wireframe jika ada

#### 🔧 Code Contributions
- Ikuti coding style yang sudah ada
- Tambahkan komentar untuk kode yang kompleks
- Test fitur yang ditambahkan
- Update dokumentasi jika diperlukan

## 🎯 Area yang Membutuhkan Kontribusi

### High Priority
- [ ] **Export Data**: Fitur export data absensi ke Excel/PDF
- [ ] **Admin Panel**: Panel admin untuk manajemen karyawan
- [ ] **Notification System**: Notifikasi untuk absen terlambat
- [ ] **API Documentation**: Dokumentasi API yang lengkap

### Medium Priority
- [ ] **Mobile App**: Aplikasi mobile native
- [ ] **Advanced Reporting**: Laporan yang lebih detail
- [ ] **Multi-language**: Support bahasa Indonesia dan Inggris
- [ ] **Theme Customization**: Kustomisasi tema dan warna

### Low Priority
- [ ] **Unit Tests**: Test coverage yang lebih lengkap
- [ ] **Performance Optimization**: Optimasi performa
- [ ] **Security Audit**: Audit keamanan yang mendalam
- [ ] **Documentation**: Dokumentasi yang lebih lengkap

## 📝 Coding Standards

### PHP
```php
// Gunakan camelCase untuk variabel
$userName = "John Doe";

// Gunakan PascalCase untuk class
class UserManager {
    // Gunakan camelCase untuk method
    public function getUserData() {
        // Kode di sini
    }
}

// Tambahkan komentar untuk method yang kompleks
/**
 * Menghitung durasi kerja berdasarkan jam masuk dan pulang
 * @param string $jamMasuk Jam masuk dalam format H:i:s
 * @param string $jamPulang Jam pulang dalam format H:i:s
 * @return array Array dengan jam dan menit
 */
public function hitungDurasiKerja($jamMasuk, $jamPulang) {
    // Implementasi
}
```

### CSS
```css
/* Gunakan kebab-case untuk class */
.login-form-container {
    background: white;
    border-radius: 15px;
}

/* Gunakan BEM methodology untuk class yang kompleks */
.card__header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.card__header--primary {
    background: linear-gradient(135deg, rgb(228, 35, 19), rgb(200, 30, 15));
}
```

### JavaScript
```javascript
// Gunakan camelCase untuk variabel dan function
function updateJam() {
    const sekarang = new Date();
    const jam = sekarang.getHours().toString().padStart(2, '0');
    // Kode di sini
}

// Gunakan const untuk variabel yang tidak berubah
const API_BASE_URL = 'http://localhost/project_telkom';
```

## 🧪 Testing

### Manual Testing
1. Test semua fitur yang ada
2. Test di berbagai browser (Chrome, Firefox, Safari, Edge)
3. Test di berbagai device (Desktop, Tablet, Mobile)
4. Test dengan data yang berbeda

### Automated Testing
- Tambahkan unit test untuk function yang kompleks
- Test database connection dan query
- Test form validation
- Test session management

## 📚 Documentation

### Code Documentation
- Tambahkan PHPDoc untuk function dan class
- Jelaskan parameter dan return value
- Tambahkan contoh penggunaan jika diperlukan

### User Documentation
- Update README.md jika ada fitur baru
- Tambahkan screenshot untuk fitur baru
- Update INSTALL.md jika ada perubahan instalasi

## 🚀 Release Process

### Version Numbering
- **Major** (1.0.0): Perubahan besar yang tidak backward compatible
- **Minor** (1.1.0): Fitur baru yang backward compatible
- **Patch** (1.0.1): Bug fix yang backward compatible

### Release Checklist
- [ ] Test semua fitur
- [ ] Update dokumentasi
- [ ] Update CHANGELOG.md
- [ ] Update version number
- [ ] Create release tag
- [ ] Deploy ke production

## 💬 Communication

### Issues
- Gunakan label yang sesuai (bug, enhancement, question)
- Berikan informasi yang lengkap
- Respon dengan cepat dan sopan

### Pull Requests
- Jelaskan perubahan yang dibuat
- Sertakan screenshot jika ada perubahan UI
- Test perubahan sebelum submit PR
- Update dokumentasi jika diperlukan

## 🏆 Recognition

Kontributor yang aktif akan:
- Dapatkan credit di README.md
- Dapatkan badge kontributor
- Dapatkan akses ke repository private
- Dapatkan prioritas untuk feature request

## 📞 Contact

Jika ada pertanyaan atau butuh bantuan:
- Buat issue di GitHub
- Email: [email protected]
- Discord: [link discord]

---

**Terima kasih atas kontribusi Anda! Mari bersama-sama membuat sistem absensi yang lebih baik! 🎉**
