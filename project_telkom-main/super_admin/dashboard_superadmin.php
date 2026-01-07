<?php
include "../config.php";

// CEK SESSION SUPER ADMIN - simple check
if (!isset($_SESSION['superadmin']) || $_SESSION['superadmin'] !== true) {
    header("Location: login_superadmin.php");
    exit();
}

// CEK apakah tb_karyawan punya kolom nik
$checkNik = mysqli_query($conn, "SHOW COLUMNS FROM tb_karyawan LIKE 'nik'");
$hasNik = mysqli_num_rows($checkNik) > 0;

// Generate CSRF Token untuk keamanan
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Query ABSENSI otomatis (join ke tb_karyawan)
if ($hasNik) {
    $today = date('Y-m-d');
    $query = "
        SELECT a.*, k.nama 
        FROM tb_absensi a 
        LEFT JOIN tb_karyawan k 
            ON CONVERT(a.nik USING utf8mb4) = CONVERT(k.nik USING utf8mb4)
        WHERE a.tanggal = '$today'
        ORDER BY a.tanggal DESC, a.jam_masuk DESC
    ";
} else {
    $today = date('Y-m-d');
    $query = "
        SELECT * 
        FROM tb_absensi
        WHERE tanggal = '$today'
        ORDER BY tanggal DESC, jam_masuk DESC
    ";
}

$dataAbsensi = mysqli_query($conn, $query);

// Get statistics
$today = date('Y-m-d');
$totalHadir = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_absensi WHERE tanggal = '$today' AND status = 'Hadir'")->fetch_assoc()['total'] ?? 0;
$totalIzin = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_absensi WHERE tanggal = '$today' AND status = 'Izin'")->fetch_assoc()['total'] ?? 0;
$totalSakit = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_absensi WHERE tanggal = '$today' AND status = 'Sakit'")->fetch_assoc()['total'] ?? 0;
$totalUser = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_karyawan WHERE account_active = 1")->fetch_assoc()['total'] ?? 0;

// CHECK AND NOTIFY USERS WITH EXPIRED INTERNSHIP (LIKE ADMIN BIASA)
$expired_users = [];
$expiring_soon = [];

// Simple query for expired users
$check_expired = mysqli_query($conn, "
    SELECT id, nik, nama, end_date 
    FROM tb_karyawan 
    WHERE end_date < '$today'
    ORDER BY end_date DESC
");

if ($check_expired) {
    while ($user = mysqli_fetch_assoc($check_expired)) {
        $expired_users[] = $user;
    }
}

// Simple query for expiring users (1-2 days)
$next_two_days = date('Y-m-d', strtotime('+2 days'));
$check_expiring = mysqli_query($conn, "
    SELECT id, nik, nama, end_date 
    FROM tb_karyawan 
    WHERE end_date BETWEEN '$today' AND '$next_two_days'
    ORDER BY end_date ASC
");

if ($check_expiring) {
    while ($user = mysqli_fetch_assoc($check_expiring)) {
        $expiring_soon[] = $user;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com;">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard Super Admin - Sistem Presensi</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--gradient-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
            border-left: 4px solid var(--telkom-primary);
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(45deg);
            pointer-events: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.success { 
            border-left-color: #28a745; 
            background: linear-gradient(145deg, #ffffff 0%, #f8fff9 100%);
        }
        
        .stat-card.warning { 
            border-left-color: #ffc107; 
            background: linear-gradient(145deg, #ffffff 0%, #fffbf0 100%);
        }
        
        .stat-card.info { 
            border-left-color: #17a2b8; 
            background: linear-gradient(145deg, #ffffff 0%, #f0f9ff 100%);
        }
        
        .stat-card.primary { 
            border-left-color: var(--telkom-primary); 
            background: linear-gradient(145deg, #ffffff 0%, #fff5f5 100%);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
            box-shadow: var(--shadow-sm);
        }

        .stat-icon.success { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
            color: white; 
        }
        
        .stat-icon.warning { 
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); 
            color: white; 
        }
        
        .stat-icon.info { 
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%); 
            color: white; 
        }
        
        .stat-icon.primary { 
            background: var(--gradient-telkom); 
            color: white; 
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--telkom-secondary);
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
            line-height: 1;
        }

        .stat-label {
            color: var(--telkom-gray);
            font-size: 14px;
            font-weight: 600;
            position: relative;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .table {
            margin-bottom: 0;
            background: white;
        }

        .table thead th {
            background: var(--gradient-telkom);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 18px 15px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table thead th:first-child {
            border-top-left-radius: 12px;
        }

        .table thead th:last-child {
            border-top-right-radius: 12px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f5;
        }

        .table tbody tr:hover {
            background-color: rgba(227, 25, 55, 0.05);
            transform: scale(1.005);
            box-shadow: var(--shadow-sm);
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table tbody td {
            padding: 16px 15px;
            vertical-align: middle;
            font-size: 14px;
        }

        .table tbody td:first-child {
            font-weight: 600;
            color: var(--telkom-secondary);
        }

        .badge {
            padding: 8px 14px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
        }

        /* Reset all badge colors first */
        .badge {
            color: inherit !important;
        }
        
        /* Override Bootstrap badge-success with maximum specificity */
        body table tbody tr td span.badge.badge-success,
        html body table tbody tr td span.badge.badge-success,
        .table-container .table tbody tr td span.badge-success,
        td span.badge-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; 
            color: #000000 !important; 
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2) !important;
            border: none !important;
        }
        
        .badge-success, 
        .badge.badge-success, 
        .table .badge-success,
        td .badge-success,
        span.badge-success { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; 
            color: #000000 !important; 
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2) !important;
        }
        
        .badge-warning { 
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); 
            color: #856404; 
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }
        
        .badge-info { 
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); 
            color: #0c5460; 
            box-shadow: 0 2px 4px rgba(23, 162, 184, 0.2);
        }
        
        .badge-danger { 
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
            color: #721c24; 
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        .badge-secondary { 
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%); 
            color: #6c757d; 
            box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--telkom-gray);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h5 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--telkom-secondary);
        }

        .empty-state p {
            font-size: 14px;
            margin: 0;
            margin-bottom: 20px;
        }

        /* Additional enhancements */
        .table-container {
            position: relative;
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
            position: relative;
            z-index: 2;
        }

        /* Improved badge animations */
        .badge {
            position: relative;
            overflow: hidden;
        }

        .badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .badge:hover::before {
            left: 100%;
        }

        /* Better button animations */
        .btn-table {
            position: relative;
            overflow: hidden;
        }

        .btn-table::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-table:hover::before {
            left: 100%;
        }

        /* Enhanced stat cards */
        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(45deg);
            pointer-events: none;
        }

        /* Better sidebar animations */
        .sidebar a {
            position: relative;
            overflow: hidden;
        }

        .sidebar a::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateY(-50%);
            transition: left 0.5s ease;
        }

        .sidebar a:hover::before {
            left: 100%;
        }

        /* Loading animation for table */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }

        .table-loading .table tbody tr {
            background: linear-gradient(90deg, #f8f9fa 25%, #e9ecef 50%, #f8f9fa 75%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }

        /* Responsive improvements */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .table-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-table {
                width: 100%;
                text-align: center;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
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

        .stat-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }
        .stat-card:nth-child(4) { animation-delay: 0.3s; }

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
            
            .stats-grid {
                grid-template-columns: 1fr;
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
        
        <a href="dashboard_superadmin.php" class="active">
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
                <i class="fas fa-chart-line"></i>
                Dashboard Super Admin
            </h1>
            <div class="page-subtitle">
                Selamat datang di panel administrasi sistem presensi magang
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value"><?= number_format($totalHadir) ?></div>
                <div class="stat-label">Hadir Hari Ini</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon warning">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-value"><?= number_format($totalIzin) ?></div>
                <div class="stat-label">Izin Hari Ini</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon info">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-value"><?= number_format($totalSakit) ?></div>
                <div class="stat-label">Sakit Hari Ini</div>
            </div>
            
            <div class="stat-card primary">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= number_format($totalUser) ?></div>
                <div class="stat-label">Total User Aktif</div>
            </div>
        </div>

        <!-- Alert untuk user yang masa magang sudah habis -->
        <?php if (!empty($expired_users)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert" style="margin-bottom: 30px;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Perhatian!</strong> Ada <?= count($expired_users) ?> user yang masa magangnya sudah habis:
                <ul class="mb-0 mt-2">
                    <?php foreach ($expired_users as $user): ?>
                        <li>
                            <strong><?= htmlspecialchars($user['nama']) ?></strong> (<?= htmlspecialchars($user['nik']) ?>) - 
                            <small>Selesai: <?= date('d/m/Y', strtotime($user['end_date'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Alert untuk user yang akan habis masa magangnya -->
        <?php if (!empty($expiring_soon)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="margin-bottom: 30px;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Info!</strong> Ada <?= count($expiring_soon) ?> User Magangnya akan habis:
                <ul class="mb-0 mt-2">
                    <?php foreach ($expiring_soon as $user): ?>
                        <li>
                            <strong><?= htmlspecialchars($user['nama']) ?></strong> (<?= htmlspecialchars($user['nik']) ?>) - 
                            <small>Selesai: <?= date('d/m/Y', strtotime($user['end_date'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-table"></i>
                    Data Absensi Terbaru
                </h2>
                <div class="table-actions">
                    <a href="riwayat_absen.php" class="btn-table">
                        <i class="fas fa-history me-1"></i>
                        Lihat Semua
                    </a>
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
                            <th>No</th>
                            <th>NIK</th>
                            <?php if ($hasNik) echo "<th>Nama Lengkap</th>"; ?>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($dataAbsensi && mysqli_num_rows($dataAbsensi) > 0) {
                            while ($row = mysqli_fetch_assoc($dataAbsensi)) { 
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
                                    <span class="badge <?= $statusClass ?>" <?= $row['status'] == 'Hadir' ? 'style="color: #000000 !important; background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; text-shadow: none !important;"' : '' ?>>
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
                                        <i class="fas fa-inbox"></i>
                                        <h5>Belum Ada Data Absensi</h5>
                                        <p>Belum ada data absensi yang tercatat dalam sistem.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Profile Super Admin
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="modal-profile-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" value="Super Admin" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="Super Administrator" readonly>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Akses Penuh</label>
                        <div>
                            <span class="badge bg-danger">Full Access</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Status</label>
                        <div>
                            <span class="badge bg-success">Aktif</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cog me-2"></i>Pengaturan Super Admin
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notifikasi Sistem</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notificationSwitch" checked>
                            <label class="form-check-label" for="notificationSwitch">
                                Aktifkan notifikasi sistem
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode Debug</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="debugSwitch">
                            <label class="form-check-label" for="debugSwitch">
                                Aktifkan mode debug
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tampilan Dashboard</label>
                        <select class="form-select">
                            <option>Default (Grid)</option>
                            <option>Compact</option>
                            <option>Detailed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Backup Otomatis</label>
                        <select class="form-select">
                            <option>Harian</option>
                            <option>Mingguan</option>
                            <option>Bulanan</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary">Simpan Pengaturan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript dengan Keamanan -->
    <script>
    // Disable right click
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Disable text selection
    document.addEventListener('selectstart', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Disable drag
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Console security
    console.clear();
    console.log('%c⚠️ WARNING!', 'color: red; font-size: 20px; font-weight: bold;');
    console.log('%cThis is a private console! Unauthorized access is prohibited.', 'color: red; font-size: 14px;');
    
    // Check for dev tools
    var devtools = {open: false, orientation: null};
    setInterval(function() {
        if(window.outerHeight - window.innerHeight > 200 || window.outerWidth - window.innerWidth > 200){
            if(!devtools.open){
                devtools.open = true;
                console.clear();
                console.log('%c🚫 Developer tools detected!', 'color: red; font-size: 16px; font-weight: bold;');
            }
        }
    }, 500);
    
    function showProfileModal() {
        var modal = new bootstrap.Modal(document.getElementById('profileModal'));
        modal.show();
    }

    function showSettingsModal() {
        var modal = new bootstrap.Modal(document.getElementById('settingsModal'));
        modal.show();
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    </script>
</body>
</html>
