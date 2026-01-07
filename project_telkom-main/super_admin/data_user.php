<?php
include "../config.php";

// Hapus user
if(isset($_GET['hapus'])){
    $id = $_GET['hapus'];
    
    // Hapus foto jika ada
    $user = mysqli_query($conn, "SELECT photo_path FROM tb_karyawan WHERE id='$id'");
    if ($userData = mysqli_fetch_assoc($user)) {
        if (!empty($userData['photo_path']) && file_exists($userData['photo_path'])) {
            unlink($userData['photo_path']);
        }
    }
    
    mysqli_query($conn, "DELETE FROM tb_karyawan WHERE id='$id'");
    header("Location: data_user.php");
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$whereClause = '';
if (!empty($search)) {
    $whereClause = "WHERE nik LIKE '%$search%' OR nama LIKE '%$search%' OR asal_sekolah LIKE '%$search%'";
}

// Count total records
$countQuery = "SELECT COUNT(*) as total FROM tb_karyawan $whereClause";
$totalResult = mysqli_query($conn, $countQuery);
$total = mysqli_fetch_assoc($totalResult)['total'];
$pages = ceil($total / $perPage);

// Get data
$data = mysqli_query($conn, "SELECT * FROM tb_karyawan $whereClause ORDER BY id DESC LIMIT $start, $perPage");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data User - Sistem Presensi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --telkom-primary: #e31937;
            --telkom-secondary: #003d7a;
            --telkom-accent: #ff6b35;
            --telkom-light: #f8f9fa;
            --telkom-gray: #6c757d;
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
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-telkom);
            border-radius: 16px 16px 0 0;
        }

        .page-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 60%;
            height: 100%;
            background: radial-gradient(circle, rgba(227, 25, 55, 0.05) 0%, transparent 70%);
            transform: rotate(45deg);
            pointer-events: none;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--telkom-secondary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
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

        .table-container {
            background: var(--gradient-card);
            border-radius: 16px;
            padding: 30px;
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
            border-radius: 16px 16px 0 0;
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
            flex-wrap: wrap;
            gap: 15px;
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
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 8px 16px 8px 40px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
            width: 250px;
        }

        .search-box input:focus {
            border-color: var(--telkom-primary);
            box-shadow: 0 0 0 0.2rem rgba(227,25,55,0.25);
            background: white;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--telkom-gray);
        }

        .btn-telkom {
            background: var(--gradient-telkom);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-telkom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-telkom:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .btn-telkom:hover::before {
            left: 100%;
        }

        .table-responsive {
            position: relative;
            z-index: 2;
        }

        .table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table thead th {
            background: var(--gradient-telkom);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 12px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 15px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f5;
            font-size: 14px;
        }

        .table tbody tr:hover {
            background: rgba(227,25,55,0.05);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--telkom-primary);
            margin-right: 12px;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: var(--telkom-secondary);
            margin-bottom: 2px;
        }

        .user-nik {
            font-size: 12px;
            color: var(--telkom-gray);
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #cfe2ff;
            color: #084298;
        }

        .btn-edit:hover {
            background: #b6d4fe;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-delete:hover {
            background: #f1aeb5;
            transform: translateY(-1px);
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
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--telkom-gray);
        }

        .empty-state i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .empty-state h5 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--telkom-secondary);
        }

        .empty-state p {
            font-size: 14px;
            margin: 0;
            margin-bottom: 20px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-container {
            animation: fadeInUp 0.6s ease-out;
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
            
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .table-actions {
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
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
        <a href="data_user.php" class="active">
            <i class="fas fa-users"></i>
            Data User
        </a>
        <a href="data_admin.php">
            <i class="fas fa-user-shield"></i>
            Data Admin
        </a>
        <a href="riwayat_absen.php">
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
                <i class="fas fa-users"></i>
                Data User
            </h1>
            <div class="page-subtitle">
                Kelola data seluruh user yang terdaftar dalam sistem
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-list"></i>
                    Daftar User
                </h2>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <form method="GET" style="display: flex;">
                            <input type="text" name="search" placeholder="Cari user..." value="<?= htmlspecialchars($search) ?>">
                        </form>
                    </div>
                    <a href="tambahuser.php" class="btn-telkom">
                        <i class="fas fa-plus"></i>
                        Tambah User
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>User</th>
                            <th>Informasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $start + 1;
                        if (mysqli_num_rows($data) > 0) {
                            while($d = mysqli_fetch_assoc($data)){ 
                                // Check if photo exists and is accessible
                                $photoPath = '../uploads/profile/default_avatar.png';
                                if (!empty($d['photo_path'])) {
                                    // Use the path as stored in database (relative to project root)
                                    if (file_exists($d['photo_path']) && is_file($d['photo_path'])) {
                                        // Use relative path from super_admin with cache busting
                                        $photoPath = '../' . $d['photo_path'] . '?t=' . time();
                                    }
                                }
                                $isActive = (empty($d['end_date']) || $d['end_date'] >= date('Y-m-d')) ? true : false;
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="user-info">
                                        <img src="<?= $photoPath ?>" alt="Avatar" class="user-avatar" onerror="this.src='../uploads/profile/default_avatar.png'; console.log('Image failed to load: <?= $photoPath ?>');">
                                        <div class="user-details">
                                            <div class="user-name"><?= htmlspecialchars($d['nama']) ?></div>
                                            <div class="user-nik"><?= htmlspecialchars($d['nik']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div><strong><?= htmlspecialchars($d['asal_sekolah'] ?? '-') ?></strong></div>
                                        <div class="text-muted small">Lantai <?= htmlspecialchars($d['lantai']) ?></div>
                                        <div class="text-muted small"><?= date('d M Y', strtotime($d['start_date'])) ?> - <?= date('d M Y', strtotime($d['end_date'])) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $isActive ? 'Aktif' : 'Tidak Aktif' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_user.php?id=<?= $d['id'] ?>" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </a>
                                        <a href="data_user.php?hapus=<?= $d['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                            <i class="fas fa-trash"></i>
                                            Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-users-slash"></i>
                                        <h5>Belum Ada Data User</h5>
                                        <p>Belum ada user yang terdaftar dalam sistem.</p>
                                        <a href="tambahuser.php" class="btn-telkom">
                                            <i class="fas fa-plus me-2"></i>
                                            Tambah User
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= htmlspecialchars($search) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= htmlspecialchars($search) ?>">
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
