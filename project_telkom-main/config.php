<?php
/**
 * Configuration File
 * Database connection and session management
 */

// =============================================================================
// SESSION MANAGEMENT
// =============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================================================
// TIMEZONE CONFIGURATION
// =============================================================================
date_default_timezone_set('Asia/Jakarta');

// =============================================================================
// DATABASE CONFIGURATION
// =============================================================================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_karyawan2";

// =============================================================================
// DATABASE CONNECTION
// =============================================================================
$conn = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// =============================================================================
// AUTO USER DEACTIVATION
// =============================================================================
// Automatically deactivate users whose internship period has ended
mysqli_query($conn, "
    UPDATE tb_karyawan
    SET account_active = 0
    WHERE end_date < CURDATE()
");

?>