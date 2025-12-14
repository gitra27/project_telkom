<?php
// koneksi ke database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_karyawan2";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

session_start();

// auto nonaktif jika masa PKL selesai
mysqli_query($conn, "
    UPDATE tb_karyawan
    SET account_active = 0
    WHERE end_date < CURDATE()
");
?>