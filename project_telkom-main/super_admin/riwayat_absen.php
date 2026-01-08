<?php
include "../config.php";

// CEK apakah tb_karyawan punya kolom nik
$checkNik = mysqli_query($conn, "SHOW COLUMNS FROM tb_karyawan LIKE 'nik'");
$hasNik = mysqli_num_rows($checkNik) > 0;

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filter setup
$filterTanggal = isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : '';
$filterBulan = isset($_GET['filter_bulan']) ? $_GET['filter_bulan'] : '';
$filterTahun = isset($_GET['filter_tahun']) ? $_GET['filter_tahun'] : '';
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$searchNama = isset($_GET['search_nama']) ? $_GET['search_nama'] : '';

// Build WHERE clause
$whereConditions = [];
if (!empty($filterTanggal)) {
    $whereConditions[] = "a.tanggal = '$filterTanggal'";
}
if (!empty($filterBulan)) {
    $whereConditions[] = "MONTH(a.tanggal) = '$filterBulan'";
}
if (!empty($filterTahun)) {
    $whereConditions[] = "YEAR(a.tanggal) = '$filterTahun'";
}
if (!empty($filterStatus)) {
    $whereConditions[] = "a.status = '$filterStatus'";
}
if (!empty($searchNama)) {
    if ($hasNik) {
        $whereConditions[] = "k.nama LIKE '%$searchNama%'";
    } else {
        $whereConditions[] = "a.nik LIKE '%$searchNama%'";
    }
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Query untuk RIWAYAT ABSENSI dengan pagination
if ($hasNik) {
    $query = "
        SELECT a.*, k.nama 
        FROM tb_absensi a 
        LEFT JOIN tb_karyawan k 
            ON CONVERT(a.nik USING utf8mb4) = CONVERT(k.nik USING utf8mb4)
        $whereClause
        ORDER BY a.tanggal DESC, a.jam_masuk DESC
        LIMIT $perPage OFFSET $offset
    ";
    
    // Query untuk total count
    $countQuery = "
        SELECT COUNT(*) as total
        FROM tb_absensi a 
        LEFT JOIN tb_karyawan k 
            ON CONVERT(a.nik USING utf8mb4) = CONVERT(k.nik USING utf8mb4)
        $whereClause
    ";
} else {
    $query = "
        SELECT * 
        FROM tb_absensi
        $whereClause
        ORDER BY tanggal DESC, jam_masuk DESC
        LIMIT $perPage OFFSET $offset
    ";
    
    $countQuery = "
        SELECT COUNT(*) as total
        FROM tb_absensi
        $whereClause
    ";
}

$dataRiwayat = mysqli_query($conn, $query);
$countResult = mysqli_query($conn, $countQuery);
$totalRecords = $countResult ? mysqli_fetch_assoc($countResult)['total'] : 0;
$totalPages = ceil($totalRecords / $perPage);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Absensi - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --telkom-primary: #e31937;
            --telkom-secondary: #003d7a;
            --telkom-accent: #ff6b35;
            --telkom-light: #f8f9fa;
            --telkom-dark: #2c3e50;
            --gradient-telkom: linear-gradient(135deg, var(--telkom-primary) 0%, var(--telkom-secondary) 100%);
            --gradient-card: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.16);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23e31937" opacity="0.03"/><circle cx="75" cy="75" r="1" fill="%23003d7a" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            pointer-events: none;
            z-index: 0;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            background: var(--gradient-telkom);
            padding: 25px 20px;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }

        .sidebar-header h4 {
            color: white;
            font-weight: 700;
            font-size: 20px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .sidebar-header .subtitle {
            color: rgba(255,255,255,0.8);
            font-size: 12px;
            margin-top: 5px;
        }

        .sidebar a {
            color: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            padding: 14px 18px;
            text-decoration: none;
            margin-bottom: 8px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            gap: 12px;
        }

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar a i {
            width: 20px;
            text-align: center;
        }

        .content {
            margin-left: 260px;
            padding: 30px;
            position: relative;
            z-index: 1;
        }

        .page-header {
            background: var(--gradient-card);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 2;
        }

        .page-title i {
            color: var(--telkom-primary);
            font-size: 24px;
        }

        .page-subtitle {
            color: var(--telkom-gray);
            margin-top: 10px;
            font-size: 16px;
            position: relative;
            z-index: 2;
            font-weight: 500;
        }

        .filter-section {
            background: var(--gradient-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .table-container {
            background: var(--gradient-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .table-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-telkom);
            border-radius: 12px 12px 0 0;
            z-index: 1;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            position: relative;
            z-index: 2;
        }

        .table-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--telkom-secondary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-title i {
            color: var(--telkom-primary);
            font-size: 18px;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .btn-table {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--telkom-primary);
            background: white;
            color: var(--telkom-primary);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-table:hover {
            background: var(--telkom-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .badge-success { background-color: #28a745; }
        .badge-info { background-color: #17a2b8; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-danger { background-color: #dc3545; }
        .badge-primary { background-color: #007bff; }
        .badge-secondary { background-color: #6c757d; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h5 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #495057;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            gap: 5px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pagination a {
            background: white;
            color: var(--telkom-secondary);
            border: 1px solid #dee2e6;
        }

        .pagination a:hover {
            background: var(--gradient-telkom);
            color: white;
            transform: translateY(-1px);
        }

        .pagination .active {
            background: var(--gradient-telkom);
            color: white;
        }

        .pagination .disabled {
            color: var(--telkom-gray);
            pointer-events: none;
            background: white;
            border: 1px solid #dee2e6;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4>
                <i class="fas fa-shield-alt"></i>
                SUPERADMIN
            </h4>
            <div class="subtitle">Sistem Presensi Magang</div>
        </div>
        
        <a href="dashboard_superadmin.php">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        <a href="#" onclick="showProfileModal()">
            <i class="fas fa-user"></i>
            Profile
        </a>
        <a href="tambah_admin.php">
            <i class="fas fa-user-plus"></i>
            Tambah Admin
        </a>
        <a href="tambahuser.php">
            <i class="fas fa-user-plus"></i>
            Tambah User
        </a>
        <a href="data_user.php">
            <i class="fas fa-users"></i>
            Data User
        </a>
        <a href="data_admin.php">
            <i class="fas fa-user-shield"></i>
            Data Admin
        </a>
        <a href="riwayat_absen.php" class="active">
            <i class="fas fa-history"></i>
            Riwayat Absen
        </a>
        <a href="#" onclick="showSettingsModal()">
            <i class="fas fa-cog"></i>
            Settings
        </a>
        <a href="logout_superadmin.php">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-history"></i>
                Riwayat Absensi
            </h1>
            <div class="page-subtitle">
                Lihat semua data riwayat absensi karyawan
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="filter_tanggal" class="form-control" value="<?= htmlspecialchars($filterTanggal) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="filter_status" class="form-select">
                        <option value="">Semua</option>
                        <option value="Hadir" <?= $filterStatus == 'Hadir' ? 'selected' : '' ?>>Hadir</option>
                        <option value="Izin" <?= $filterStatus == 'Izin' ? 'selected' : '' ?>>Izin</option>
                        <option value="Sakit" <?= $filterStatus == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                        <option value="Telat" <?= $filterStatus == 'Telat' ? 'selected' : '' ?>>Telat</option>
                        <option value="Selesai" <?= $filterStatus == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="riwayat_absen.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-table"></i>
                    Data Riwayat Absensi
                </h2>
                <div class="table-actions">
                    <div class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Total <?= number_format($totalRecords) ?> data
                    </div>
                    <a href="export_absen.php" class="btn-table">
                        <i class="fas fa-download me-1"></i>
                        Export
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th class="text-center">NIK</th>
                            <?php if ($hasNik) echo "<th class=\"text-center\">Nama Lengkap</th>"; ?>
                            <th class="text-center">Tanggal</th>
                            <th class="text-center">Jam Masuk</th>
                            <th class="text-center">Jam Pulang</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        if ($dataRiwayat && mysqli_num_rows($dataRiwayat) > 0) {
                            while ($row = mysqli_fetch_assoc($dataRiwayat)) { 
                                $statusClass = '';
                                $statusIcon = '';
                                
                                switch($row['status']) {
                                    case 'Hadir':
                                        $statusClass = 'badge-success';
                                        $statusIcon = 'fa-check-circle';
                                        break;
                                    case 'Izin':
                                        $statusClass = 'badge-info';
                                        $statusIcon = 'fa-info-circle';
                                        break;
                                    case 'Sakit':
                                        $statusClass = 'badge-warning';
                                        $statusIcon = 'fa-exclamation-triangle';
                                        break;
                                    case 'Telat':
                                        $statusClass = 'badge-danger';
                                        $statusIcon = 'fa-clock';
                                        break;
                                    case 'Selesai':
                                        $statusClass = 'badge-primary';
                                        $statusIcon = 'fa-check-double';
                                        break;
                                    default:
                                        $statusClass = 'badge-secondary';
                                        $statusIcon = 'fa-question-circle';
                                }
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($row['nik']) ?></strong></td>
                                <?php if ($hasNik) { ?>
                                    <td><?= htmlspecialchars($row['nama'] ?? 'Tidak ada nama') ?></td>
                                <?php } ?>
                                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= !empty($row['jam_masuk']) ? htmlspecialchars($row['jam_masuk']) : '<span class="text-muted">-</span>' ?></td>
                                <td><?= !empty($row['jam_pulang']) ? htmlspecialchars($row['jam_pulang']) : '<span class="text-muted">-</span>' ?></td>
                                <td>
                                    <span class="badge <?= $statusClass ?>">
                                        <i class="fas <?= $statusIcon ?>"></i>
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="<?= $hasNik ? 7 : 6 ?>">
                                    <div class="empty-state">
                                        <i class="fas fa-history"></i>
                                        <h5>Tidak Ada Data Riwayat</h5>
                                        <p>Tidak ada data riwayat absensi yang sesuai dengan filter yang dipilih.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= !empty($filterTanggal) ? '&filter_tanggal=' . $filterTanggal : '' ?><?= !empty($filterBulan) ? '&filter_bulan=' . $filterBulan : '' ?><?= !empty($filterTahun) ? '&filter_tahun=' . $filterTahun : '' ?><?= !empty($filterStatus) ? '&filter_status=' . $filterStatus : '' ?><?= !empty($searchNama) ? '&search_nama=' . $searchNama : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php 
                // Show page ranges (1-10, 11-20, etc.)
                $rangeSize = 10;
                for ($start = 1; $start <= $totalPages; $start += $rangeSize):
                    $end = min($start + $rangeSize - 1, $totalPages);
                    $isInRange = ($page >= $start && $page <= $end);
                ?>
                    <?php if ($isInRange): ?>
                        <span class="active"><?= $start ?>-<?= $end ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $start ?><?= !empty($filterTanggal) ? '&filter_tanggal=' . $filterTanggal : '' ?><?= !empty($filterBulan) ? '&filter_bulan=' . $filterBulan : '' ?><?= !empty($filterTahun) ? '&filter_tahun=' . $filterTahun : '' ?><?= !empty($filterStatus) ? '&filter_status=' . $filterStatus : '' ?><?= !empty($searchNama) ? '&search_nama=' . $searchNama : '' ?>"><?= $start ?>-<?= $end ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($filterTanggal) ? '&filter_tanggal=' . $filterTanggal : '' ?><?= !empty($filterBulan) ? '&filter_bulan=' . $filterBulan : '' ?><?= !empty($filterTahun) ? '&filter_tahun=' . $filterTahun : '' ?><?= !empty($filterStatus) ? '&filter_status=' . $filterStatus : '' ?><?= !empty($searchNama) ? '&search_nama=' . $searchNama : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
