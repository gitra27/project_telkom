<?php
include "config.php";

// proteksi login
if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit();
}

$nik  = $_SESSION['nik'];
$nama = $_SESSION['nama'];

// Prevent SQL injection with prepared statements
$stmt = mysqli_prepare($conn, "SELECT * FROM tb_absensi WHERE nik = ? ORDER BY tanggal DESC, jam_masuk DESC");
mysqli_stmt_bind_param($stmt, "s", $nik);
mysqli_stmt_execute($stmt);
$q = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Absensi - <?= $nama ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .header-telkom {
            background: #ff0033;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .btn-telkom {
            background: #ff0033;
            color: white;
        }
        .btn-telkom:hover {
            background: #cc002a;
            color: white;
        }
    </style>
</head>

<body class="bg-light">

<div class="container mt-4">

    <div class="header-telkom">
        <h3 class="mb-0">📄 Riwayat Absensi</h3>
        <small>User: <?= $nama ?> (<?= $nik ?>)</small>
    </div>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Lokasi</th>
                        <th>Foto</th>
                        <th>File</th>
                        <th>Catatan</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($row = mysqli_fetch_assoc($q)): ?>

                    <?php
                    // link google maps
                    $maps = "https://www.google.com/maps?q=" . $row['latitude'] . "," . $row['longitude'];
                    ?>

                    <tr>
                        <td><?= $row['tanggal'] ?></td>

                        <td>
                            <?php if ($row['status'] == "Hadir"): ?>
                                <span class="badge bg-success">Hadir</span>
                            <?php elseif ($row['status'] == "Sakit"): ?>
                                <span class="badge bg-warning text-dark">Sakit</span>
                            <?php elseif ($row['status'] == "Izin"): ?>
                                <span class="badge bg-primary">Izin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= $row['status'] ?></span>
                            <?php endif; ?>
                        </td>

                        <td><?= $row['jam_masuk'] ?></td>

                        <td>
                            <?= ($row['jam_pulang'] == NULL || $row['jam_pulang'] == "") 
                                ? "<span class='text-danger'>Belum Absen</span>" 
                                : $row['jam_pulang']; ?>
                        </td>

                        <td>
                            <a href="<?= $maps ?>" target="_blank" class="btn btn-sm btn-danger">
                                📍 Lihat Lokasi
                            </a>
                        </td>

                        <td>
                            <?php if ($row['foto'] != "" && file_exists("uploads/foto/".$row['foto'])): ?>
                                <img src="uploads/foto/<?= $row['foto'] ?>" width="60" class="rounded shadow">
                            <?php else: ?>
                                <span class="text-muted">Tidak ada foto</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($row['file_upload'] != "" && file_exists("uploads/file/".$row['file_upload'])): ?>
                                <a href="uploads/file/<?= $row['file_upload'] ?>" download class="btn btn-sm btn-secondary">
                                    📎 Download
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada file</span>
                            <?php endif; ?>
                        </td>

                        <td><?= $row['catatan'] ?></td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

</body>
</html>