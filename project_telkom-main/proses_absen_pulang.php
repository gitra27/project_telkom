<?php
include "config.php";

$id    = $_POST['id_absen'];
$jam   = date("H:i:s");
$lat   = $_POST['latitude'];
$long  = $_POST['longitude'];

mysqli_query($conn, 
"UPDATE tb_absensi 
SET jam_pulang='$jam', latitude_pulang='$lat', longitude_pulang='$long'
WHERE id='$id'");

header("Location: dashboard.php");
exit();
?>