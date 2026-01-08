<?php
session_start();
include '../config.php';

// Get admin role from session
$admin_role = $_SESSION['admin_role'] ?? 'admin';

if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// Get admin data
$admin_nik = $_SESSION['admin'];

// Ensure admin_nik is a string before sanitizing
if (is_array($admin_nik)) {
    $admin_nik = $admin_nik[0] ?? '';
}

// Sanitize input to prevent SQL injection
$admin_nik = mysqli_real_escape_string($conn, $admin_nik);

// Fetch admin data from database
$admin_query = mysqli_query($conn, "SELECT * FROM tb_karyawan WHERE nik = '$admin_nik'") or die(mysqli_error($conn));
$admin_data = mysqli_fetch_assoc($admin_query);

// Set admin data with fallback
$admin_name = isset($admin_data['nama']) ? $admin_data['nama'] : 'Administrator';
$admin_nik_display = isset($admin_data['nik']) ? $admin_data['nik'] : $admin_nik;
$admin_location = isset($admin_data['lokasi']) ? $admin_data['lokasi'] : 'Admin Area';
$admin_role_display = $admin_role === 'superadmin' ? 'Super Administrator' : 'Administrator';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate current password
    $check_password = mysqli_query($conn, "SELECT password FROM tb_karyawan WHERE nik = '$admin_nik'") or die(mysqli_error($conn));
    $password_data = mysqli_fetch_assoc($check_password);
    
    if ($password_data && password_verify($current_password, $password_data['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE tb_karyawan SET password = '$hashed_password' WHERE nik = '$admin_nik'") or die(mysqli_error($conn));
                $success_message = "Password berhasil diubah!";
            } else {
                $error_message = "Password minimal 6 karakter!";
            }
        } else {
            $error_message = "Password baru tidak cocok!";
        }
    } else {
        $error_message = "Password saat ini salah!";
    }
}

// Ambil data untuk dashboard
$total_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_karyawan") or die(mysqli_error($conn));
$total_users_row = mysqli_fetch_assoc($total_users);
$total_users = $total_users_row['total'];

$active_users = mysqli_query($conn, "SELECT COUNT(*) as active FROM tb_karyawan WHERE account_active = 1") or die(mysqli_error($conn));
$active_users_row = mysqli_fetch_assoc($active_users);
$active_users = $active_users_row['active'];

$today_absen = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_absensi WHERE tanggal = CURDATE()") or die(mysqli_error($conn));
$today_absen_row = mysqli_fetch_assoc($today_absen);
$today_absen = $today_absen_row['total'];

$today_hadir = mysqli_query($conn, "SELECT COUNT(*) as hadir FROM tb_absensi WHERE tanggal = CURDATE() AND status = 'Hadir'") or die(mysqli_error($conn));
$today_hadir_row = mysqli_fetch_assoc($today_hadir);
$today_hadir = $today_hadir_row['hadir'];

$today_izin = mysqli_query($conn, "SELECT COUNT(*) as izin FROM tb_absensi WHERE tanggal = CURDATE() AND status = 'Izin'") or die(mysqli_error($conn));
$today_izin_row = mysqli_fetch_assoc($today_izin);
$today_izin = $today_izin_row['izin'];

$today_sakit = mysqli_query($conn, "SELECT COUNT(*) as sakit FROM tb_absensi WHERE tanggal = CURDATE() AND status = 'Sakit'") or die(mysqli_error($conn));
$today_sakit_row = mysqli_fetch_assoc($today_sakit);
$today_sakit = $today_sakit_row['sakit'];

$recent_absen = mysqli_query($conn, "SELECT a.*, k.nama FROM tb_absensi a JOIN tb_karyawan k ON a.nik = k.nik ORDER BY a.tanggal DESC, a.jam_masuk DESC LIMIT 10") or die(mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Presensi Magang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --telkom-primary: #e31937;
            --telkom-secondary: #003d7a;
            --telkom-accent: #ff6b35;
            --telkom-light: #f8f9fa;
            --telkom-dark: #2c3e50;
            --telkom-gray: #6c757d;
            --telkom-success: #28a745;
            --telkom-warning: #ffc107;
            --telkom-danger: #dc3545;
            --gradient-telkom: linear-gradient(135deg, var(--telkom-primary) 0%, var(--telkom-secondary) 100%);
            --gradient-card: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.2);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--telkom-light);
            margin: 0;
            padding: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: var(--gradient-telkom);
            color: white;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            padding: 25px 20px;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: 70px;
            padding: 25px 10px;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }

        .sidebar-header h3 {
            color: white;
            font-weight: 700;
            font-size: 18px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .sidebar.collapsed .sidebar-header h3 {
            font-size: 12px;
        }

        .sidebar-header .subtitle {
            color: rgba(255,255,255,0.8);
            font-size: 12px;
            margin-top: 5px;
        }

        .header-location {
            color: rgba(255,255,255,0.9);
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
            padding: 4px 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar.collapsed .sidebar-header .subtitle {
            display: none;
        }

        .sidebar-menu {
            padding: 0;
        }

        .menu-section {
            margin-bottom: 25px;
        }

        .menu-section-title {
            padding: 10px 18px;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 15px;
        }

        .sidebar.collapsed .menu-section-title {
            display: none;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            margin-bottom: 8px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .menu-badge {
            position: absolute;
            top: 8px;
            right: 10px;
            background: var(--telkom-accent);
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
        }

        .sidebar.collapsed .menu-badge {
            position: relative;
            top: auto;
            right: auto;
            margin-top: 5px;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }

        .menu-item.active {
            background: rgba(255,255,255,0.25);
            color: white;
            border-left: 4px solid var(--telkom-accent);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar.collapsed .menu-item {
            padding: 12px;
            justify-content: center;
            text-align: center;
        }

        .sidebar.collapsed .menu-item i {
            margin: 0;
        }

        .sidebar.collapsed .menu-item span {
            display: none;
        }

        /* Animasi untuk menu item */
        .menu-item::before {
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

        .menu-item:hover::before {
            left: 100%;
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
        }

        .main-content::before {
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

        .main-content.expanded {
            margin-left: 70px;
        }

        .top-header {
            background: var(--gradient-card);
            padding: 25px 30px;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 16px 16px;
            border: 1px solid rgba(255,255,255,0.2);
            border-top: none;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--telkom-secondary);
            margin: 0;
        }

        .header-welcome {
            color: var(--telkom-gray);
            margin: 8px 0 0 0;
            font-size: 14px;
            font-weight: 500;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .header-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .header-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--telkom-secondary);
        }

        .header-role {
            font-size: 12px;
            color: var(--telkom-gray);
            margin-top: 2px;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
        }

        .quick-action-btn {
            background: var(--telkom-primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .quick-action-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .fa-spin {
            animation: spin 1s linear infinite;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 30px;
            padding: 0 10px;
            position: relative;
            z-index: 1;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.08);
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
            text-align: center;
            overflow: hidden;
        }

        .stat-card.success {
            border-left: 4px solid #28a745;
        }
        
        .stat-card.warning {
            border-left: 4px solid #ffc107;
        }
        
        .stat-card.info {
            border-left: 4px solid #17a2b8;
        }
        
        .stat-card.primary {
            border-left: 4px solid #e31937;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            font-size: 18px;
            color: white;
            position: relative;
            z-index: 2;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .stat-icon.primary { 
            background: #e31937;
        }
        
        .stat-icon.success { 
            background: #28a745;
        }
        
        .stat-icon.warning { 
            background: #ffc107;
        }
        
        .stat-icon.info { 
            background: #17a2b8;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
            line-height: 1;
        }

        .stat-label {
            color: #6c757d;
            font-size: 11px;
            font-weight: 600;
            position: relative;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0;
            padding: 0;
        }

        .header-info {
            display: none;
        }

        .quick-actions {
            display: none;
        }

        .recent-activity {
            background: var(--gradient-card);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            margin: 0 10px;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .recent-activity::before {
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

        .activity-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            position: relative;
            z-index: 2;
        }

        .activity-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: var(--telkom-secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-header h2 i {
            color: var(--telkom-primary);
            font-size: 18px;
        }

        .table-actions {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .table-actions .btn-refresh {
            background: var(--telkom-primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .table-actions .btn-refresh:hover {
            background: var(--telkom-secondary);
            transform: scale(1.05);
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .attendance-table th {
            background: var(--gradient-telkom);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .attendance-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            vertical-align: middle;
        }

        .attendance-table tbody tr:hover {
            background: #f8f9fa;
        }

        .attendance-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-btn {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .status-hadir {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-selesai {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b3d7ff;
        }

        .status-izin {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .status-sakit {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-other {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        .text-center {
            text-align: center;
        }

        .activity-header .btn-refresh {
            background: var(--telkom-primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: auto;
            position: relative;
            overflow: hidden;
        }

        .activity-header .btn-refresh::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .activity-header .btn-refresh:hover::before {
            left: 100%;
        }

        .activity-header .btn-refresh:hover {
            background: var(--telkom-secondary);
            transform: scale(1.05);
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: var(--telkom-light);
            padding-left: 10px;
            margin: 0 -10px;
            padding-right: 10px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
        }

        .activity-details {
            flex: 1;
        }

        .activity-name {
            font-weight: 600;
            color: var(--telkom-secondary);
            margin-bottom: 3px;
            font-size: 14px;
        }

        .activity-info {
            font-size: 12px;
            color: #666;
        }

        .activity-status {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-hadir {
            background: #d4edda;
            color: #155724;
        }

        .status-izin {
            background: #cce5ff;
            color: #004085;
        }
            }

            .header-role {
                font-size: 12px;
                color: var(--telkom-gray);
                padding: 4px 12px;
                background: var(--telkom-light);
                border-radius: 20px;
                font-weight: 500;
            }

            .quick-actions {
                display: flex;
                gap: 15px;
                margin-bottom: 30px;
                flex-wrap: wrap;
            }

            .quick-action-btn {
                background: var(--gradient-telkom);
                color: white;
                border: none;
                border-radius: 12px;
                padding: 12px 20px;
                font-size: 14px;
                font-weight: 600;
                transition: all 0.3s ease;
                cursor: pointer;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .quick-action-btn:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-md);
            }

            .recent-activity {
                background: var(--gradient-card);
                border-radius: 16px;
                padding: 25px;
                box-shadow: var(--shadow-md);
                margin: 0 10px;
                border: 1px solid rgba(255,255,255,0.2);
                backdrop-filter: blur(10px);
                position: relative;
                z-index: 1;
                overflow: hidden;
            }

            .recent-activity::before {
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

            .activity-header {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 2px solid #e9ecef;
                position: relative;
                z-index: 2;
            }

            .activity-header h2 {
                margin: 0;
                font-size: 20px;
                font-weight: 600;
                color: var(--telkom-secondary);
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .activity-header h2 i {
                color: var(--telkom-primary);
                font-size: 18px;
            }

            .activity-header .btn-refresh {
                background: var(--telkom-primary);
                color: white;
                border: none;
                border-radius: 8px;
                padding: 8px 16px;
                font-size: 12px;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-left: auto;
                position: relative;
                overflow: hidden;
            }

            .activity-header .btn-refresh::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                transition: left 0.5s ease;
            }

            .activity-header .btn-refresh:hover::before {
                left: 100%;
            }

            .activity-header .btn-refresh:hover {
                background: var(--telkom-secondary);
                transform: scale(1.05);
            }

            .activity-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .activity-item {
                display: flex;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #f0f0f0;
                transition: all 0.3s ease;
            }

            .activity-item:hover {
                background: var(--telkom-light);
                padding-left: 10px;
                margin: 0 -10px;
                padding-right: 10px;
            }

            .activity-item:last-child {
                border-bottom: none;
            }

            .activity-avatar {
                width: 35px;
                height: 35px;
                border-radius: 50%;
                margin-right: 12px;
                object-fit: cover;
                background: #f0f0f0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #999;
                font-size: 14px;
            }

            .activity-details {
                flex: 1;
            }

            .activity-name {
                font-weight: 600;
                color: var(--telkom-secondary);
                margin-bottom: 3px;
                font-size: 14px;
            }

            .activity-info {
                font-size: 12px;
                color: #666;
            }

            .activity-status {
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }

            .status-hadir {
                background: #d4edda;
                color: #155724;
            }

            .status-izin {
                background: #cce5ff;
                color: #004085;
            }

            .status-sakit {
                background: #fff3cd;
                color: #856404;
            }

            /* Modal Styles */
            .modal {
                display: none;
                position: fixed;
                z-index: 2000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
            }

            .modal-content {
                background-color: white;
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                padding: 0;
                border-radius: 16px;
                width: 90%;
                max-width: 320px;
                box-shadow: var(--shadow-lg);
                animation: modalSlideIn 0.3s ease;
            }

            @keyframes modalSlideIn {
                from {
                    transform: translate(-50%, -50%) scale(0.8);
                    opacity: 0;
                }
                to {
                    transform: translate(-50%, -50%) scale(1);
                    opacity: 1;
                }
            }

            .modal-header {
                background: var(--gradient-telkom);
                color: white;
                padding: 20px 30px;
                border-radius: 16px 16px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .modal-header h2 {
                margin: 0;
                font-size: 20px;
                font-weight: 600;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .close {
                color: white;
                font-size: 24px;
                font-weight: bold;
                cursor: pointer;
                background: none;
                border: none;
                transition: all 0.3s ease;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .close:hover {
                transform: scale(1.1);
            }

            .modal-body {
                padding: 20px;
            }

            .profile-info {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .profile-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px solid #e9ecef;
            }

            .profile-item:last-child {
                border-bottom: none;
            }

            .profile-label {
                font-weight: 600;
                color: var(--telkom-secondary);
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .profile-value {
                font-size: 16px;
                color: #333;
                font-weight: 500;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .status-badge {
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .status-active {
                background: #d4edda;
                color: #155724;
            }

            .access-badge {
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                background: #f8d7da;
                color: #721c24;
            }

            .modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #e9ecef;
                text-align: center;
                background: #f8f9fa;
                border-radius: 0 0 16px 16px;
            }

            .btn-close {
                background: var(--gradient-telkom);
                color: white;
                border: none;
                border-radius: 6px;
                padding: 10px 20px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                min-width: 80px;
            }

            .btn-close:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            }

            .btn-change-password {
                background: var(--gradient-telkom);
                color: white;
                border: none;
                border-radius: 6px;
                padding: 8px 16px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .btn-change-password:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                color: var(--telkom-secondary);
                font-size: 13px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .form-group input {
                width: 100%;
                padding: 10px 12px;
                border: 2px solid #e9ecef;
                border-radius: 6px;
                font-size: 13px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                transition: all 0.3s ease;
            }

            .form-group input:focus {
                outline: none;
                border-color: var(--telkom-primary);
                box-shadow: 0 0 0 3px rgba(225, 25, 55, 0.1);
            }

            .btn-submit {
                background: var(--gradient-telkom);
                color: white;
                border: none;
                border-radius: 6px;
                padding: 12px 20px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                width: 100%;
            }

            .btn-submit:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            }

            .alert {
                padding: 10px 15px;
                border-radius: 6px;
                margin-bottom: 15px;
                font-size: 13px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .alert-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .alert-danger {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>
                <i class="fas fa-user-shield"></i>
                ADMIN
            </h3>
            <div class="subtitle">Sistem Presensi Magang</div>
    </div>
        <div class="sidebar-menu">
            <!-- Menu Utama -->
            <div class="menu-section">
                <div class="menu-section-title">Menu Utama</div>
                <a href="admin_dashboard_new.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="menu-item" onclick="openProfileModal()">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="data_user.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Data User</span>
                </a>
                <a href="logout_admin.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
            
            <!-- Menu User (hanya untuk superadmin) -->
            <?php if ($admin_role === 'superadmin'): ?>
            <div class="menu-section">
                <div class="menu-section-title">Manajemen User</div>
                <a href="admin_tambah_admin.php" class="menu-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Tambah Admin</span>
                    <?php
                    // Hitung jumlah admin yang belum diverifikasi
                    $unverified_admins = mysqli_query($conn, "SELECT COUNT(*) as count FROM tb_karyawan WHERE level = 'admin' AND account_active = 0") or die(mysqli_error($conn));
                    $unverified_count = mysqli_fetch_assoc($unverified_admins)['count'];
                    if ($unverified_count > 0) {
                        echo '<div class="menu-badge">' . $unverified_count . '</div>';
                    }
                    ?>
                </a>
                <a href="admin_data_admin.php" class="menu-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Data Admin</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Header -->
        <div class="top-header">
            <div class="header-left">
                <h1 class="header-title">Dashboard Admin</h1>
                <p class="header-welcome">Selamat datang di Dashboard Presensi Magang Telkom Indonesia</p>
            </div>
            <div class="header-right">
                <div class="header-info">
                    <div class="header-name"><?= htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="header-role">
                        <?php 
                        if ($admin_role === 'superadmin') {
                            echo 'Super Admin';
                        } else {
                            echo 'Admin';
                        }
                        ?>
                    </div>
                    <div class="header-location"><?= htmlspecialchars($admin_location, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
        </div>
        <!-- Content Area -->
        <div class="content-area">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-icon success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-value"><?= number_format($today_hadir, 0, ',', '.') ?></div>
                    <div class="stat-label">HADIR HARI INI</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon warning">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-value"><?= number_format($today_izin, 0, ',', '.') ?></div>
                    <div class="stat-label">IZIN HARI INI</div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon info">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="stat-value"><?= number_format($today_sakit, 0, ',', '.') ?></div>
                    <div class="stat-label">SAKIT HARI INI</div>
                </div>
                
                <div class="stat-card primary">
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?= number_format($active_users, 0, ',', '.') ?></div>
                    <div class="stat-label">TOTAL USER AKTIF</div>
                </div>
            </div>

            <!-- Data Absensi Terbaru -->
            <div class="recent-activity">
                <div class="activity-header">
                    <h2><i class="fas fa-clock"></i> Data Absensi Terbaru</h2>
                    <div class="table-actions">
                        <button class="btn-refresh" onclick="exportData()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>NIK</th>
                                <th>NAMA LENGKAP</th>
                                <th>TANGGAL</th>
                                <th>JAM MASUK</th>
                                <th>JAM PULANG</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $today_absen_data = mysqli_query($conn, "SELECT a.*, k.nama FROM tb_absensi a JOIN tb_karyawan k ON a.nik = k.nik WHERE a.tanggal = CURDATE() ORDER BY a.jam_masuk DESC") or die(mysqli_error($conn));
                            $no = 1;
                            if (mysqli_num_rows($today_absen_data) > 0): 
                                while ($row = mysqli_fetch_assoc($today_absen_data)):
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nik']) ?></td>
                                <td><?= htmlspecialchars($row['nama'] ?? 'Tidak ada nama') ?></td>
                                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['jam_masuk'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['jam_pulang'] ?? '-') ?></td>
                                <td>
                                    <?php 
                                    $status = strtolower($row['status']);
                                    if ($status == 'hadir') {
                                        echo '<span class="status-btn status-hadir">Hadir</span>';
                                    } elseif ($status == 'selesai') {
                                        echo '<span class="status-btn status-selesai">Selesai</span>';
                                    } elseif ($status == 'izin') {
                                        echo '<span class="status-btn status-izin">Izin</span>';
                                    } elseif ($status == 'sakit') {
                                        echo '<span class="status-btn status-sakit">Sakit</span>';
                                    } else {
                                        echo '<span class="status-btn status-other">' . htmlspecialchars($row['status']) . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data absensi hari ini</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleBtn = document.getElementById('toggleSidebar');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            if (sidebar.classList.contains('collapsed')) {
                toggleBtn.style.marginLeft = '70px';
            } else {
                toggleBtn.style.marginLeft = '260px';
            }
        }

        function refreshData() {
            location.reload();
        }

        function exportData() {
            // Create a simple CSV export for today's attendance
            const table = document.querySelector('.attendance-table');
            let csv = [];
            
            // Get headers
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent);
            csv.push(headers.join(','));
            
            // Get rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const rowData = Array.from(row.querySelectorAll('td')).map(td => {
                    // Remove status button HTML and get text only
                    const text = td.textContent.trim();
                    return text.includes('\n') ? text.split('\n')[0].trim() : text;
                });
                csv.push(rowData.join(','));
            });
            
            // Create download
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'absensi_hari_ini_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            console.log('Auto-refreshing data...');
            // You can add AJAX refresh here if needed
        }, 30000);

        // Profile Modal Functions
        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'block';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const profileModal = document.getElementById('profileModal');
            const passwordModal = document.getElementById('passwordModal');
            
            if (event.target == profileModal) {
                closeProfileModal();
            }
            if (event.target == passwordModal) {
                closePasswordModal();
            }
        }

        // Password Modal Functions
        function openPasswordModal() {
            closeProfileModal();
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Password tidak cocok!');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Profile Admin</h2>
                <button class="close" onclick="closeProfileModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="profile-info">
                    <div class="profile-item">
                        <span class="profile-label">Nama Admin</span>
                        <span class="profile-value"><?= htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">NIK Admin</span>
                        <span class="profile-value"><?= htmlspecialchars($admin_nik_display, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Lantai Admin</span>
                        <span class="profile-value"><?= htmlspecialchars($admin_location, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Ubah Password</span>
                        <button class="btn-change-password" onclick="openPasswordModal()">Ubah Password</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ubah Password</h2>
                <button class="close" onclick="closePasswordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?= $success_message ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Password Saat Ini</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Simpan Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
