<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$nik = $_SESSION['nik'];
$tanggal = date("Y-m-d");
$aksi = $_POST['aksi'];

if ($aksi == "masuk") {
    $jam = date("H:i:s");
    $conn->query("INSERT INTO tb_absensi (nik, tanggal, jam_masuk) VALUES ('$nik','$tanggal','$jam')");
} elseif ($aksi == "pulang") {
    $jam = date("H:i:s");
    $conn->query("UPDATE tb_absensi SET jam_pulang='$jam' WHERE nik='$nik' AND tanggal='$tanggal'");
}

header("Location: dashboard.php");
exit;
?>
