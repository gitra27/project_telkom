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

// Create upload directories if they don't exist
if (!is_dir('uploads/foto')) {
    mkdir('uploads/foto', 0777, true);
}
if (!is_dir('uploads/file')) {
    mkdir('uploads/file', 0777, true);
}

// upload FOTO with validation
$fotoName = "";
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $fotoName = time() . "_" . basename($_FILES['foto']['name']);
        move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/foto/" . $fotoName);
    }
}

// upload FILE OPSIONAL with validation
$fileName = "";
if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0 && $_FILES['file_upload']['name'] != "") {
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $fileName = time() . "_" . basename($_FILES['file_upload']['name']);
        move_uploaded_file($_FILES['file_upload']['tmp_name'], "uploads/file/" . $fileName);
    }
}

// Prevent SQL injection with prepared statements
$stmt = mysqli_prepare($conn, 
    "INSERT INTO tb_absensi (nik, tanggal, jam_masuk, status, catatan, foto, file_upload, latitude, longitude)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sssssssss", $nik, $tgl, $jam, $status, $catatan, $fotoName, $fileName, $lat, $long);
mysqli_stmt_execute($stmt);

header("Location: dashboard.php");
exit();
?>