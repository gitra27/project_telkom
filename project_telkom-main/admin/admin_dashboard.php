<?php
session_start();

$conn = mysqli_connect("localhost","root","","db_karyawan2");
if(!$conn){
    die("koneksi database gagal");
}


if(!isset($_SESSION['admin'])){
    header("location: login_admin.php");
    exit;
}

$nama_admin   = $_SESSION['admin']['nama_admin'];
$lantai_admin = $_SESSION['admin']['lantai'];

if(isset($_POST['tambah_user'])){
    $nik_user  = $_POST['nik_user'];
    $nama_user = $_POST['nama_user'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    mysqli_query($conn,"
        INSERT INTO tb_user (nik, nama, lantai, password, created_at)
        VALUES (
            '$nik_user',
            '$nama_user',
            '$lantai_admin',
            '$password',
            NOW()
        )
    ");
}

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

$q_summary = mysqli_query($conn,"
    SELECT 
        COUNT(*) as total,
        SUM(a.status='hadir') as hadir,
        SUM(a.status='izin') as izin,
        SUM(a.status='alpha') as alpha
    FROM tb_absensi a
    JOIN tb_karyawan k ON a.nik = k.nik
    WHERE k.lantai='$lantai_admin'
    AND MONTH(a.tanggal)='$bulan'
    AND YEAR(a.tanggal)='$tahun'
");
$summary = mysqli_fetch_assoc($q_summary);

$q_user = mysqli_query($conn,"
    SELECT * FROM tb_karyawan
    WHERE lantai='$lantai_admin'
    ORDER BY nama ASC
");

$q_absen = mysqli_query($conn,"
    SELECT a.*, k.nama, k.lantai 
    FROM tb_absensi a
    JOIN tb_karyawan k ON a.nik = k.nik
    WHERE k.lantai='$lantai_admin'
    ORDER BY a.tanggal DESC, a.jam_masuk DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Sistem Absensi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="style_admin.css">
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-chart-line"></i> Absensi System</h3>
        </div>
        <nav class="sidebar-menu">
            <a href="#" class="menu-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Data Magang dan PKL</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-calendar-check"></i>
                <span>Absensi</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Laporan</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Pengaturan</span>
            </a>
            <a href="login_admin.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navigation -->
        <div class="top-nav">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search...">
            </div>
            <div class="nav-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar">
                    <img src="../uploads/profile/default_avatar.png" alt="Admin Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>
                    <div class="user-info">
                        <h4>Admin</h4>
                        <p>Lantai <?= htmlspecialchars($lantai_admin) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Absensi</div>
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $summary['total'] ?? 0 ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 12% dari bulan lalu
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Hadir</div>
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $summary['hadir'] ?? 0 ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> 8% dari bulan lalu
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Izin</div>
                    <div class="stat-icon yellow">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $summary['izin'] ?? 0 ?></div>
                <div class="stat-change negative">
                    <i class="fas fa-arrow-down"></i> 3% dari bulan lalu
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Alpha</div>
                    <div class="stat-icon red">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?= $summary['alpha'] ?? 0 ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-down"></i> 5% dari bulan lalu
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">Filter Data Absensi</h3>
                <div class="chart-tabs">
                    <button class="tab-btn active">Hari Ini</button>
                    <button class="tab-btn">Minggu Ini</button>
                    <button class="tab-btn">Bulan Ini</button>
                </div>
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-control">
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= ($bulan == $i) ? 'selected' : '' ?>>
                                <?= date('F', mktime(0,0,0,$i,1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-control">
                        <?php for($i=date('Y'); $i>=date('Y')-5; $i--): ?>
                            <option value="<?= $i ?>" <?= ($tahun == $i) ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-auto">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="row">
            <!-- Tambah User Form -->
            <div class="col-lg-4">
                <div class="form-container">
                    <h3 class="chart-title">Tambah User</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">NIK User</label>
                            <input type="text" name="nik_user" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama User</label>
                            <input type="text" name="nama_user" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password Awal</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah_user" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Tambah User
                        </button>
                    </form>
                </div>
            </div>

            <!-- Data User Table -->
            <div class="col-lg-8">
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Data User</h3>
                        <div class="table-actions">
                            <button class="btn-sm btn-primary">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Lantai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = mysqli_fetch_assoc($q_user)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['nik']) ?></td>
                                    <td><?= htmlspecialchars($user['nama']) ?></td>
                                    <td><?= htmlspecialchars($user['lantai']) ?></td>
                                    <td>
                                        <span class="status-badge active">Active</span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Riwayat Absensi Table -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">Riwayat Absensi</h3>
                <div class="table-actions">
                    <button class="btn-sm">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <button class="btn-sm btn-primary">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>Masuk</th>
                        <th>Pulang</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($absen = mysqli_fetch_assoc($q_absen)): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($absen['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($absen['nama']) ?></td>
                            <td><?= $absen['jam_masuk'] ?: '-' ?></td>
                            <td><?= $absen['jam_pulang'] ?: '-' ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($absen['status']) ?>">
                                    <?= htmlspecialchars($absen['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
