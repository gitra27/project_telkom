<?php
include 'config.php';
if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}
$nik = $_SESSION['nik'];
$tanggal = date("Y-m-d");
$aksi = $_POST['aksi'];

if ($aksi == "masuk") {
    $jam = date("H:i:s");
    // Prevent SQL injection
    $stmt = mysqli_prepare($conn, "INSERT INTO tb_absensi (nik, tanggal, jam_masuk) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $nik, $tanggal, $jam);
    mysqli_stmt_execute($stmt);
} elseif ($aksi == "pulang") {
    $jam = date("H:i:s");
    // Prevent SQL injection
    $stmt = mysqli_prepare($conn, "UPDATE tb_absensi SET jam_pulang = ? WHERE nik = ? AND tanggal = ?");
    mysqli_stmt_bind_param($stmt, "sss", $jam, $nik, $tanggal);
    mysqli_stmt_execute($stmt);
}

header("Location: dashboard.php");
exit;
?>
