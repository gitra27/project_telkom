<?php
/**
 * Script Setup Database Otomatis
 * Jalankan file ini sekali untuk membuat database dan tabel
 */

$host = "localhost";
$user = "root";
$pass = "";

// Koneksi tanpa database terlebih dahulu
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$db_name = "db_karyawan";

// Buat database jika belum ada
$sql = "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "✓ Database '$db_name' berhasil dibuat atau sudah ada<br>";
} else {
    die("Error membuat database: " . $conn->error);
}

// Pilih database
$conn->select_db($db_name);

// Buat tabel tb_karyawan
$sql = "CREATE TABLE IF NOT EXISTS tb_karyawan (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Tabel 'tb_karyawan' berhasil dibuat atau sudah ada<br>";
} else {
    die("Error membuat tabel tb_karyawan: " . $conn->error);
}

// Buat tabel tb_absensi
// Hapus tabel jika sudah ada untuk memastikan struktur benar
$conn->query("DROP TABLE IF EXISTS tb_absensi");

$sql = "CREATE TABLE tb_absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_pulang TIME,
    status ENUM('hadir', 'terlambat', 'tidak_hadir', 'izin', 'sakit') DEFAULT 'hadir',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_absensi (nik, tanggal),
    INDEX idx_absensi_tanggal (tanggal),
    FOREIGN KEY (nik) REFERENCES tb_karyawan(nik) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Tabel 'tb_absensi' berhasil dibuat<br>";
} else {
    // Jika foreign key gagal, buat tanpa foreign key (untuk kompatibilitas)
    $sql_no_fk = "CREATE TABLE tb_absensi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nik VARCHAR(20) NOT NULL,
        tanggal DATE NOT NULL,
        jam_masuk TIME,
        jam_pulang TIME,
        status ENUM('hadir', 'terlambat', 'tidak_hadir', 'izin', 'sakit') DEFAULT 'hadir',
        keterangan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_absensi (nik, tanggal),
        INDEX idx_absensi_tanggal (tanggal)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_no_fk) === TRUE) {
        echo "✓ Tabel 'tb_absensi' berhasil dibuat (tanpa foreign key constraint)<br>";
    } else {
        die("Error membuat tabel tb_absensi: " . $conn->error);
    }
}

// Buat index tambahan untuk optimasi (jika belum ada)
$indexes = [
    ["name" => "idx_karyawan_status", "table" => "tb_karyawan", "columns" => "(status)"]
];

// Catatan: 
// - idx_absensi_tanggal sudah dibuat saat CREATE TABLE
// - idx_absensi_nik_tanggal tidak perlu karena sudah ada UNIQUE KEY unique_absensi (nik, tanggal)
// - idx_karyawan_nik tidak perlu karena kolom nik sudah UNIQUE (otomatis punya index)

foreach ($indexes as $index) {
    // Cek apakah index sudah ada
    $check_sql = "SHOW INDEX FROM {$index['table']} WHERE Key_name = '{$index['name']}'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows == 0) {
        // Index belum ada, buat index dengan ALTER TABLE
        $alter_sql = "ALTER TABLE {$index['table']} ADD INDEX {$index['name']} {$index['columns']}";
        if ($conn->query($alter_sql) === TRUE) {
            echo "✓ Index '{$index['name']}' berhasil dibuat<br>";
        } else {
            // Jika masih error, skip (mungkin sudah ada dengan nama lain)
            echo "⚠ Index '{$index['name']}' mungkin sudah ada atau gagal dibuat: " . $conn->error . "<br>";
        }
    } else {
        echo "✓ Index '{$index['name']}' sudah ada<br>";
    }
}

echo "✓ Index optimasi selesai<br>";

// Cek apakah sudah ada data karyawan
$result = $conn->query("SELECT COUNT(*) as count FROM tb_karyawan");
$row = $result->fetch_assoc();
$count = $row['count'];

if ($count == 0) {
    // Insert data karyawan contoh (password: 123456)
    $password_hash = password_hash('123456', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO tb_karyawan (nik, nama, password, jabatan, departemen, email, telepon) VALUES
    ('1234567890123456', 'Ahmad Wijaya', '$password_hash', 'Manager', 'IT', 'ahmad.wijaya@telkom.co.id', '081234567890'),
    ('2345678901234567', 'Siti Nurhaliza', '$password_hash', 'Staff', 'HR', 'siti.nurhaliza@telkom.co.id', '081234567891'),
    ('3456789012345678', 'Budi Santoso', '$password_hash', 'Supervisor', 'Finance', 'budi.santoso@telkom.co.id', '081234567892'),
    ('4567890123456789', 'Dewi Kartika', '$password_hash', 'Staff', 'Marketing', 'dewi.kartika@telkom.co.id', '081234567893'),
    ('5678901234567890', 'Rizki Pratama', '$password_hash', 'Staff', 'IT', 'rizki.pratama@telkom.co.id', '081234567894')";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Data karyawan contoh berhasil ditambahkan (5 karyawan)<br>";
        echo "<br><strong>Kredensial Login:</strong><br>";
        echo "NIK: 1234567890123456<br>";
        echo "Password: 123456<br>";
    } else {
        echo "⚠ Error menambahkan data karyawan: " . $conn->error . "<br>";
    }
} else {
    echo "✓ Database sudah berisi $count karyawan<br>";
}

// Buat view statistik (opsional) - skip jika ada error
$sql = "CREATE OR REPLACE VIEW v_statistik_absensi AS
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
GROUP BY k.nik, k.nama, k.jabatan, k.departemen";

// Cek apakah tabel tb_absensi memiliki kolom nik
$check_absensi = $conn->query("SHOW COLUMNS FROM tb_absensi LIKE 'nik'");
if ($check_absensi && $check_absensi->num_rows > 0) {
    // Hapus view jika sudah ada
    $conn->query("DROP VIEW IF EXISTS v_statistik_absensi");
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ View 'v_statistik_absensi' berhasil dibuat<br>";
    } else {
        echo "⚠ View 'v_statistik_absensi' gagal dibuat (opsional, tidak mempengaruhi fungsi utama): " . $conn->error . "<br>";
    }
} else {
    echo "⚠ View 'v_statistik_absensi' dilewati karena struktur tabel belum lengkap<br>";
}

$conn->close();

echo "<br><hr>";
echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
echo "<h3 style='color: #155724; margin-top: 0;'>✓ Setup Database Selesai!</h3>";
echo "<p><strong>Langkah selanjutnya:</strong></p>";
echo "<ol>";
echo "<li>Buka <a href='login.php' style='color: #e42313; font-weight: bold;'>halaman login</a> untuk mulai menggunakan sistem</li>";
echo "<li>Login dengan kredensial berikut:</li>";
echo "</ol>";
echo "<div style='background: white; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<strong>NIK:</strong> <code>1234567890123456</code><br>";
echo "<strong>Password:</strong> <code>123456</code>";
echo "</div>";
echo "<p><a href='login.php' style='display: inline-block; padding: 12px 30px; background: #e42313; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; font-weight: bold;'>🚀 Masuk ke Login</a></p>";
echo "<p style='font-size: 12px; color: #666; margin-top: 20px;'>";
echo "⚠️ <strong>Penting:</strong> Setelah setup selesai, disarankan untuk menghapus atau rename file <code>setup.php</code> untuk keamanan.";
echo "</p>";
echo "</div>";
?>

