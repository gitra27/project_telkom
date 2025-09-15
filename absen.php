<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$tanggal = date("Y-m-d");
$aksi    = $_POST['aksi'];

if ($aksi == "masuk") {
    $jam = date("H:i:s");
    $conn->query("INSERT INTO tb_absensi (user_id, tanggal, jam_masuk) VALUES ('$user_id','$tanggal','$jam')");
} elseif ($aksi == "pulang") {
    $jam = date("H:i:s");
    $conn->query("UPDATE tb_absensi SET jam_pulang='$jam' WHERE user_id='$user_id' AND tanggal='$tanggal'");
}

header("Location: index.php");
exit;
?>
