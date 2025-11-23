<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_karyawan";

// Cek apakah ini file setup.php, jika ya, skip pengecekan tabel
$is_setup = (basename($_SERVER['PHP_SELF']) == 'setup.php');

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // Jika database tidak ada, coba buat
    if ($conn->connect_errno == 1049) {
        // Database tidak ada, buat koneksi tanpa database
        $conn_temp = new mysqli($host, $user, $pass);
        if (!$conn_temp->connect_error) {
            $conn_temp->query("CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn_temp->close();
            // Coba koneksi lagi
            $conn = new mysqli($host, $user, $pass, $db);
        }
    }
    
    if ($conn->connect_error && !$is_setup) {
        $error_msg = "<!DOCTYPE html>
        <html>
        <head>
            <title>Database Error</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .error-box { background: #fee; border: 2px solid #fcc; padding: 20px; border-radius: 5px; }
                .btn { display: inline-block; padding: 10px 20px; background: #e42313; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
                .btn:hover { background: #c01e0f; }
            </style>
        </head>
        <body>
            <div class='error-box'>
                <h2>⚠️ Database Belum Di-Setup</h2>
                <p>Database <strong>$db</strong> belum dibuat atau tidak dapat diakses.</p>
                <p><strong>Solusi:</strong></p>
                <ol>
                    <li>Klik tombol di bawah untuk setup database secara otomatis</li>
                    <li>Atau jalankan file <code>setup_database.sql</code> di phpMyAdmin</li>
                </ol>
                <a href='setup.php' class='btn'>🚀 Setup Database Sekarang</a>
            </div>
        </body>
        </html>";
        die($error_msg);
    }
}

// Cek apakah tabel ada (skip jika ini setup.php)
if (!$is_setup) {
    function checkTableExists($conn, $tableName) {
        $result = $conn->query("SHOW TABLES LIKE '$tableName'");
        return $result && $result->num_rows > 0;
    }

    // Cek tabel penting
    if (!checkTableExists($conn, 'tb_karyawan')) {
        $error_msg = "<!DOCTYPE html>
        <html>
        <head>
            <title>Setup Required</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
                .error-box { background: white; border: 2px solid #e42313; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h2 { color: #e42313; margin-top: 0; }
                .btn { display: inline-block; padding: 12px 30px; background: #e42313; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; font-weight: bold; }
                .btn:hover { background: #c01e0f; }
                code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class='error-box'>
                <h2>⚠️ Database Belum Di-Setup</h2>
                <p>Tabel <strong>tb_karyawan</strong> tidak ditemukan di database.</p>
                <p><strong>Langkah Setup:</strong></p>
                <ol>
                    <li>Klik tombol di bawah untuk setup database secara otomatis</li>
                    <li>Atau buka file <code>setup.php</code> di browser</li>
                    <li>Atau import file <code>setup_database.sql</code> di phpMyAdmin</li>
                </ol>
                <a href='setup.php' class='btn'>🚀 Setup Database Sekarang</a>
                <p style='margin-top: 20px; font-size: 12px; color: #666;'>
                    Setelah setup selesai, halaman ini akan otomatis berfungsi.
                </p>
            </div>
        </body>
        </html>";
        die($error_msg);
    }

    if (!checkTableExists($conn, 'tb_absensi')) {
        $error_msg = "<!DOCTYPE html>
        <html>
        <head>
            <title>Setup Required</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
                .error-box { background: white; border: 2px solid #e42313; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h2 { color: #e42313; margin-top: 0; }
                .btn { display: inline-block; padding: 12px 30px; background: #e42313; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; font-weight: bold; }
                .btn:hover { background: #c01e0f; }
            </style>
        </head>
        <body>
            <div class='error-box'>
                <h2>⚠️ Database Belum Di-Setup</h2>
                <p>Tabel <strong>tb_absensi</strong> tidak ditemukan di database.</p>
                <p>Silakan setup database terlebih dahulu.</p>
                <a href='setup.php' class='btn'>🚀 Setup Database Sekarang</a>
            </div>
        </body>
        </html>";
        die($error_msg);
    }
}
?>
