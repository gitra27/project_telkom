<?php
include "config.php";

$nik     = $_SESSION['nik'];
$nama    = $_SESSION['nama'];
$tgl     = date("Y-m-d");
$jam     = date("H:i:s");

$status  = $_POST['status'];
$catatan = $_POST['catatan'];
$lat     = $_POST['latitude'];
$long    = $_POST['longitude'];

// upload FOTO
$fotoName = time() . "_" . $_FILES['foto']['name'];
move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/foto/" . $fotoName);

// upload FILE OPSIONAL
$fileName = "";
if ($_FILES['file_upload']['name'] != "") {
    $fileName = time() . "_" . $_FILES['file_upload']['name'];
    move_uploaded_file($_FILES['file_upload']['tmp_name'], "uploads/file/" . $fileName);
}

// simpan absensi
mysqli_query($conn, 
"INSERT INTO tb_absensi (nik, tanggal, jam_masuk, status, catatan, foto, file_upload, latitude, longitude)
VALUES ('$nik','$tgl','$jam','$status','$catatan','$fotoName','$fileName','$lat','$long')");

header("Location: dashboard.php");
exit();
?>