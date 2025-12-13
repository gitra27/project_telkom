-- Script untuk membuat database dan tabel absensi karyawan
-- Jalankan script ini di phpMyAdmin atau MySQL command line

CREATE DATABASE IF NOT EXISTS db_karyawan;
USE db_karyawan;

-- Tabel untuk data karyawan
CREATE TABLE IF NOT EXISTS tb_karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    jabatan VARCHAR(50),
    departemen VARCHAR(50),
    email VARCHAR(100),
    telepon VARCHAR(15),
    alamat TEXT,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel untuk data absensi
CREATE TABLE IF NOT EXISTS tb_absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_pulang TIME,
    status ENUM('hadir', 'terlambat', 'tidak_hadir', 'izin', 'sakit') DEFAULT 'hadir',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nik) REFERENCES tb_karyawan(nik) ON DELETE CASCADE,
    UNIQUE KEY unique_absensi (nik, tanggal)
);

-- Insert data karyawan contoh (password: 123456)
INSERT INTO tb_karyawan (nik, nama, password, jabatan, departemen, email, telepon) VALUES
('1234567890123456', 'Ahmad Wijaya', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'IT', 'ahmad.wijaya@telkom.co.id', '081234567890'),
('2345678901234567', 'Siti Nurhaliza', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'HR', 'siti.nurhaliza@telkom.co.id', '081234567891'),
('3456789012345678', 'Budi Santoso', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor', 'Finance', 'budi.santoso@telkom.co.id', '081234567892'),
('4567890123456789', 'Dewi Kartika', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'Marketing', 'dewi.kartika@telkom.co.id', '081234567893'),
('5678901234567890', 'Rizki Pratama', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'IT', 'rizki.pratama@telkom.co.id', '081234567894');

-- Insert data absensi contoh untuk beberapa hari terakhir
INSERT INTO tb_absensi (nik, tanggal, jam_masuk, jam_pulang, status) VALUES
-- Ahmad Wijaya
('1234567890123456', CURDATE(), '08:00:00', '17:00:00', 'hadir'),
('1234567890123456', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:15:00', '17:30:00', 'terlambat'),
('1234567890123456', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '07:45:00', '16:45:00', 'hadir'),
('1234567890123456', DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00:00', '17:00:00', 'hadir'),
('1234567890123456', DATE_SUB(CURDATE(), INTERVAL 4 DAY), '08:30:00', '17:15:00', 'terlambat'),

-- Siti Nurhaliza
('2345678901234567', CURDATE(), '08:00:00', '17:00:00', 'hadir'),
('2345678901234567', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 'hadir'),
('2345678901234567', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00', '17:00:00', 'hadir'),
('2345678901234567', DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00:00', '17:00:00', 'hadir'),
('2345678901234567', DATE_SUB(CURDATE(), INTERVAL 4 DAY), '08:00:00', '17:00:00', 'hadir'),

-- Budi Santoso
('3456789012345678', CURDATE(), '08:00:00', '17:00:00', 'hadir'),
('3456789012345678', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 'hadir'),
('3456789012345678', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00', '17:00:00', 'hadir'),
('3456789012345678', DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00:00', '17:00:00', 'hadir'),
('3456789012345678', DATE_SUB(CURDATE(), INTERVAL 4 DAY), '08:00:00', '17:00:00', 'hadir');

-- Index untuk optimasi query
CREATE INDEX idx_absensi_nik_tanggal ON tb_absensi(nik, tanggal);
CREATE INDEX idx_absensi_tanggal ON tb_absensi(tanggal);
CREATE INDEX idx_karyawan_nik ON tb_karyawan(nik);
CREATE INDEX idx_karyawan_status ON tb_karyawan(status);

-- View untuk statistik absensi
CREATE VIEW v_statistik_absensi AS
SELECT 
    k.nik,
    k.nama,
    k.jabatan,
    k.departemen,
    COUNT(a.id) as total_hari,
    COUNT(CASE WHEN a.jam_masuk IS NOT NULL THEN 1 END) as hari_hadir,
    COUNT(CASE WHEN a.jam_pulang IS NOT NULL THEN 1 END) as hari_lengkap,
    COUNT(CASE WHEN a.status = 'terlambat' THEN 1 END) as hari_terlambat,
    COUNT(CASE WHEN a.jam_masuk IS NULL THEN 1 END) as hari_tidak_hadir
FROM tb_karyawan k
LEFT JOIN tb_absensi a ON k.nik = a.nik
WHERE k.status = 'aktif'
GROUP BY k.nik, k.nama, k.jabatan, k.departemen;
