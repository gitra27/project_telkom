<?php
include 'config.php';
session_start();

if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}

$nik = $_SESSION['nik'];
$aksi = $_POST['aksi'];
$tanggal = date("Y-m-d");
$jam = date("H:i:s");

// CEK apakah sudah ada absen hari ini
$cek = $conn->query("SELECT * FROM tb_absensi WHERE nik='$nik' AND tanggal='$tanggal'");
$ada = $cek->fetch_assoc();

if ($aksi == "masuk") {
    if ($ada) {
        header("Location: dashboard.php?err=sudah_absen_masuk");
        exit;
    }

    // Tentukan status (telat atau tidak)
    $status = ($jam > "08:00:00") ? "terlambat" : "hadir";

    $conn->query("INSERT INTO tb_absensi (nik, tanggal, jam_masuk, status) 
                  VALUES ('$nik', '$tanggal', '$jam', '$status')");

} elseif ($aksi == "pulang") {

    if (!$ada) {
        header("Location: dashboard.php?err=belum_absen_masuk");
        exit;
    }

    if ($ada['jam_pulang'] != null) {
        header("Location: dashboard.php?err=sudah_absen_pulang");
        exit;
    }

    $conn->query("UPDATE tb_absensi 
                  SET jam_pulang='$jam' 
                  WHERE nik='$nik' AND tanggal='$tanggal'");
}

// Redirect user kembali ke dashboard
header("Location: dashboard.php?success=1");
exit;
?>