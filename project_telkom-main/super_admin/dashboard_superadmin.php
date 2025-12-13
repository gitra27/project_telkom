<?php
include "../config.php";

// CEK apakah tb_karyawan punya kolom nik
$checkNik = mysqli_query($conn, "SHOW COLUMNS FROM tb_karyawan LIKE 'nik'");
$hasNik = mysqli_num_rows($checkNik) > 0;

// Query ABSENSI otomatis (join ke tb_karyawan)
if ($hasNik) {
    $query = "
        SELECT a.*, k.nama 
        FROM tb_absensi a 
        LEFT JOIN tb_karyawan k 
            ON CONVERT(a.nik USING utf8mb4) = CONVERT(k.nik USING utf8mb4)
        ORDER BY a.tanggal DESC, a.jam_masuk DESC
    ";
} else {
    $query = "
        SELECT *
        FROM tb_absensi
        ORDER BY tanggal DESC, jam_masuk DESC
    ";
}

$dataAbsensi = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Super Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <style>
        body { background: #f4f6f9; }
        .sidebar {
            width: 240px;
            height: 100vh;
            background: #303841;
            padding: 20px;
            position: fixed;
        }
        .sidebar a {
            color: white;
            display: block;
            padding: 12px;
            text-decoration: none;
            margin-bottom: 8px;
            border-radius: 6px;
        }
        .sidebar a:hover { background: #3a4750; }
        .content { margin-left: 260px; padding: 20px; }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
        .title { font-size: 26px; font-weight: 600; }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h4 class="text-white mb-4">SUPERADMIN</h4>

        <a href="dashboard_superadmin.php">📊 Dashboard</a>
        <a href="tambah_user.php">➕ Tambah User</a>
        <a href="data_user.php">👥 Data User</a>
        <a href="riwayat_absen.php">📜 Riwayat Absen</a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <div class="title mb-3">Dashboard Absensi</div>

        <div class="table-container shadow">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>NIK</th>
                        <?php if ($hasNik) echo "<th>Nama</th>"; ?>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php 
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($dataAbsensi)) { 
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $row['nik'] ?></td>

                            <?php if ($hasNik) { ?>
                                <td><?= $row['nama'] ?></td>
                            <?php } ?>

                            <td><?= $row['tanggal'] ?></td>
                            <td><?= $row['jam_masuk'] ?></td>
                            <td><?= $row['jam_pulang'] ?></td>
                            <td><?= $row['status'] ?></td>
                        </tr>
                    <?php } ?>
                </tbody>

            </table>
        </div>
    </div>

</body>
</html>
