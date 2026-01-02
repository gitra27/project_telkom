<?php
include "../config.php";

if (isset($_POST['save'])) {
    $nama   = $_POST['nama_admin'];
    $nik    = $_POST['nik_admin'];
    $lantai = $_POST['lantai'];
    $pass   = password_hash($_POST['password_admin'], PASSWORD_DEFAULT);

    // CEK NIK SUDAH ADA
    $cek = mysqli_query($conn, "SELECT nik_admin FROM tb_admin WHERE nik_admin='$nik'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('NIK ADMIN sudah dipakai!'); window.location='tambah_admin.php';</script>";
        exit();
    }

    $q = mysqli_query($conn, "
        INSERT INTO tb_admin(nama_admin, nik_admin, password_admin, lantai)
        VALUES('$nama', '$nik', '$pass', '$lantai')
    ");

    if ($q) {
        echo "<script>alert('Admin berhasil ditambahkan!'); window.location='dashboard_superadmin.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan admin!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Admin - Sistem Presensi</title>
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

        .form-container {
            background: var(--gradient-card);
            border-radius: 16px;
            padding: 40px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-telkom);
            border-radius: 16px 16px 0 0;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
        }

        .form-label {
            font-weight: 600;
            color: var(--telkom-secondary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--telkom-primary);
            font-size: 14px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
        }

        .form-control:focus {
            border-color: var(--telkom-primary);
            box-shadow: 0 0 0 0.2rem rgba(227,25,55,0.25);
            background: white;
        }

        .btn-telkom {
            background: var(--gradient-telkom);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            width: 100%;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
            color: white;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            position: relative;
            z-index: 2;
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

        .form-container {
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
            
            .form-container {
                padding: 25px;
            }
            
            .form-actions {
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
        <a href="tambah_admin.php" class="active">
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
                <i class="fas fa-user-plus"></i>
                Tambah Admin Baru
            </h1>
            <div class="page-subtitle">
                Tambahkan administrator baru untuk mengelola sistem presensi
            </div>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>
                        Nama Admin
                    </label>
                    <input type="text" name="nama_admin" class="form-control" placeholder="Masukkan nama lengkap admin" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i>
                        NIK Admin
                    </label>
                    <input type="text" name="nik_admin" class="form-control" placeholder="Masukkan NIK admin" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-building"></i>
                        Lantai Admin
                    </label>
                    <select name="lantai" class="form-control" required>
                        <option value="">- PILIH LANTAI -</option>
                        <option value="1">Lantai 1</option>
                        <option value="2">Lantai 2</option>
                        <option value="3">Lantai 3</option>
                        <option value="5">Lantai 5</option>
                        <option value="6">Lantai 6</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i>
                        Password Admin
                    </label>
                    <input type="password" name="password_admin" class="form-control" placeholder="Masukkan password admin" required>
                </div>

                <div class="form-actions">
                    <button type="submit" name="save" class="btn-telkom">
                        <i class="fas fa-save me-2"></i>
                        Simpan Admin
                    </button>
                    <a href="dashboard_superadmin.php" class="btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
