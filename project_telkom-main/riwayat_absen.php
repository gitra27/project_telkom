<?php
include "config.php";

// proteksi login
if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit();
}

$nik  = $_SESSION['nik'];
$nama = $_SESSION['nama'] ?? 'User';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #c3cfe2;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .header-telkom {
            background: linear-gradient(135deg, #e31937, #003d7a);
            color: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            border-left: 5px solid #e31937;
        }
        
        .header-telkom h3 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-telkom small {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 2px solid #e31937;
            padding: 20px;
            font-weight: 600;
            color: #003d7a;
        }
        
        .table {
            margin: 0;
            font-size: 14px;
        }
        
        .table th {
            background: linear-gradient(135deg, #003d7a, #e31937);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 12px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 12px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .bg-success {
            background: #d4edda;
            color: #155724;
        }
        
        .bg-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .bg-primary {
            background: #cce5ff;
            color: #004085;
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .rounded {
            border-radius: 12px;
        }
        
        .shadow {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .text-muted {
            color: #6c757d;
            font-style: italic;
        }
        
        .text-danger {
            color: #dc3545;
            font-weight: 600;
        }
    </style>
</head>

<body>

<div class="container mt-4">

    <div class="header-telkom">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 class="mb-0">📄 Riwayat Absensi</h3>
                <small>User: <?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($nik, ENT_QUOTES, 'UTF-8') ?>)</small>
            </div>
            <a href="dashboard.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">📊 Data Riwayat Absensi</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> Tanggal</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-sign-in-alt"></i> Jam Masuk</th>
                        <th><i class="fas fa-sign-out-alt"></i> Jam Pulang</th>
                        <th><i class="fas fa-map-marker-alt"></i> Lokasi</th>
                        <th><i class="fas fa-camera"></i> Foto</th>
                        <th><i class="fas fa-file"></i> File</th>
                        <th><i class="fas fa-sticky-note"></i> Catatan</th>
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