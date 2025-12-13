<?php
include "../config.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Absen</title>
</head>
<body>

<h2>Riwayat Absensi</h2>

<form method="GET">
    <input type="date" name="tanggal">
    <input type="text" name="nik" placeholder="Cari NIK">
    <button type="submit">Filter</button>
</form>

<br>

<table border="1" cellpadding="8">
    <tr>
        <th>No</th>
        <th>NIK</th>
        <th>Nama</th>
        <th>Tanggal</th>
        <th>Jam Masuk</th>
        <th>Jam Pulang</th>
        <th>Status</th>
        <th>Lokasi</th>
    </tr>

<?php
$query = "SELECT a.*, u.nama FROM tb_absensi a 
          LEFT JOIN tb_users u ON a.nik = u.nik WHERE 1=1";

// filter tanggal
if(!empty($_GET['tanggal'])){
    $tgl = $_GET['tanggal'];
    $query .= " AND a.tanggal = '$tgl'";
}

// filter nik
if(!empty($_GET['nik'])){
    $nik = $_GET['nik'];
    $query .= " AND a.nik LIKE '%$nik%'";
}

$query .= " ORDER BY a.id_absen DESC";

$no = 1;
$data = mysqli_query($conn, $query);
while($d = mysqli_fetch_array($data)){
?>
    <tr>
        <td><?= $no++; ?></td>
        <td><?= $d['nik']; ?></td>
        <td><?= $d['nama']; ?></td>
        <td><?= $d['tanggal']; ?></td>
        <td><?= $d['jam_masuk']; ?></td>
        <td><?= $d['jam_pulang']; ?></td>
        <td><?= $d['status']; ?></td>
        <td><?= $d['latitude']; ?> , <?= $d['longitude']; ?></td>
    </tr>
<?php } ?>
</table>

</body>
</html>
