<?php
include "../config.php";

if (isset($_POST['save'])) {
    
    $nama   = trim($_POST['nama']);
    $nik    = trim($_POST['nik']);
    $asal   = trim($_POST['asal_sekolah']);
    $lantai = trim($_POST['lantai']);
    $start  = trim($_POST['start_date']);
    $end    = trim($_POST['end_date']);
    $password = trim($_POST['password']);
    
    // Handle photo upload
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= $max_size) {
            $upload_dir = '../uploads/profile/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $file_name = 'user_' . $nik . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_path = $upload_path;
            }
        }
    }

    // Validasi input
    if (empty($nama) || empty($nik) || empty($password)) {
        echo "<script>alert('Nama, NIK, dan password wajib diisi!');</script>";
    } else {
        // Cek apakah NIK sudah ada
        $check_query = mysqli_prepare($conn, "SELECT nik FROM tb_karyawan WHERE nik = ?");
        mysqli_stmt_bind_param($check_query, "s", $nik);
        mysqli_stmt_execute($check_query);
        $result = mysqli_stmt_get_result($check_query);
        
        if (mysqli_num_rows($result) > 0) {
            echo "<script>alert('NIK $nik sudah terdaftar! Gunakan NIK yang lain.');</script>";
        } else {
            // Hash password agar aman
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Query insert user baru dengan prepared statement
            $insert_query = mysqli_prepare($conn, "
                INSERT INTO tb_karyawan(nama, nik, password, asal_sekolah, lantai, start_date, end_date, photo_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            mysqli_stmt_bind_param($insert_query, "sssssss", 
                $nama, $nik, $password_hash, $asal, $lantai, $start, $end, $photo_path);
            
            if (mysqli_stmt_execute($insert_query)) {
                echo "<script>
                        alert('User berhasil ditambahkan!');
                        window.location='data_user.php';
                      </script>";
            } else {
                echo "<script>alert('Gagal menambahkan user!');</script>";
            }
            
            mysqli_stmt_close($insert_query);
        }
        
        mysqli_stmt_close($check_query);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User - Sistem Presensi</title>
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
            max-width: 700px;
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

        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
        }

        .form-select:focus {
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

        .row {
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
        <a href="tambah_admin.php">
            <i class="fas fa-user-plus"></i>
            Tambah Admin 
        </a>
        <a href="tambahuser.php" class="active">
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
                <i class="fas fa-user-plus"></i>
                Tambah User Baru
            </h1>
            <div class="page-subtitle">
                Tambahkan user baru untuk sistem presensi
            </div>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user"></i>
                                Nama Lengkap
                            </label>
                            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap user" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-id-card"></i>
                                NIK User
                            </label>
                            <input type="text" name="nik" class="form-control" placeholder="Masukkan NIK user" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-school"></i>
                                Asal Sekolah
                            </label>
                            <input type="text" name="asal_sekolah" class="form-control" placeholder="Masukkan asal sekolah" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-building"></i>
                                Lantai Penempatan
                            </label>
                            <select name="lantai" class="form-select" required>
                                <option value="">- PILIH LANTAI -</option>
                                <option value="1">Lantai 1</option>
                                <option value="2">Lantai 2</option>
                                <option value="3">Lantai 3</option>
                                <option value="5">Lantai 5</option>
                                <option value="6">Lantai 6</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar-plus"></i>
                                Tanggal Mulai PKL
                            </label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar-minus"></i>
                                Tanggal Berakhir PKL
                            </label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i>
                        Password Akun
                    </label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password untuk akun user" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-camera"></i>
                        Foto Profil
                    </label>
                    <input type="file" name="photo" class="form-control" accept="image/*" onchange="previewPhoto(event)" style="border: 2px solid #e9ecef; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease; background: rgba(255,255,255,0.9);">
                    <div class="mt-3 text-center">
                        <img id="photoPreview" src="../uploads/profile/default_avatar.png" alt="Preview" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid var(--telkom-primary); display: block; box-shadow: var(--shadow-md);">
                        <div id="placeholderIcon" style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%); display: none; align-items: center; justify-content: center; margin: 0 auto; border: 3px solid #dee2e6;">
                            <i class="fas fa-camera" style="font-size: 40px; color: #6c757d;"></i>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Format: JPG, PNG, GIF. Maksimal 2MB
                    </small>
                </div>

                <div class="form-actions">
                    <button type="submit" name="save" class="btn-telkom">
                        <i class="fas fa-save me-2"></i>
                        Simpan User
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
    
    <script>
        function previewPhoto(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('photoPreview');
            const placeholder = document.getElementById('placeholderIcon');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                }
                reader.readAsDataURL(file);
            } else {
                preview.src = '../uploads/profile/default_avatar.png';
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            }
        }
        
        // Initialize with default avatar
        document.addEventListener('DOMContentLoaded', function() {
            const preview = document.getElementById('photoPreview');
            const placeholder = document.getElementById('placeholderIcon');
            if (preview) {
                preview.src = '../uploads/profile/default_avatar.png';
                preview.style.display = 'block';
            }
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        });
    </script>
</body>
</html>
