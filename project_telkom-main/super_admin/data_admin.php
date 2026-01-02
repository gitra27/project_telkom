<?php
include "../config.php";

// Hapus admin
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM tb_admin WHERE id_admin=$id");
    echo "<script>alert('Admin dihapus!'); window.location='data_admin.php';</script>";
}

// Jika tombol update ditekan
if (isset($_POST['update'])) {
    $id   = $_POST['id_admin'];
    $nama = $_POST['nama_admin'];
    $nik  = $_POST['nik_admin'];
    $lantai = $_POST['lantai'];

    // Jika password diisi → update password
    if (!empty($_POST['password_admin'])) {
        $pw = password_hash($_POST['password_admin'], PASSWORD_DEFAULT);
        $query = "
            UPDATE tb_admin SET
            nama_admin='$nama',
            nik_admin='$nik',
            lantai='$lantai',
            password_admin='$pw'
            WHERE id_admin='$id'
        ";
    } else {
        $query = "
            UPDATE tb_admin SET
            nama_admin='$nama',
            nik_admin='$nik',
            lantai='$lantai'
            WHERE id_admin='$id'
        ";
    }

    mysqli_query($conn, $query);
    echo "<script>alert('Data admin diperbarui!'); window.location='data_admin.php';</script>";
}

$admins = mysqli_query($conn, "SELECT * FROM tb_admin ORDER BY lantai ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Admin - Sistem Presensi</title>
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

        .btn-telkom {
            background: var(--gradient-telkom);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-telkom:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--telkom-secondary);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background-color: rgba(227, 25, 55, 0.05);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-edit {
            background: #17a2b8;
            color: white;
        }

        .btn-edit:hover {
            background: #138496;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            color: white;
        }

        .form-section {
            background: var(--gradient-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .form-label {
            font-weight: 600;
            color: var(--telkom-secondary);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--telkom-primary);
            box-shadow: 0 0 0 0.2rem rgba(227, 25, 55, 0.25);
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
        <a href="data_admin.php" class="active">
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
                <i class="fas fa-user-shield"></i>
                Data Admin
            </h1>
            <div class="page-subtitle">
                Kelola data administrator sistem
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-table"></i>
                    Daftar Admin
                </h2>
                <div class="table-actions">
                    <a href="tambah_admin.php" class="btn-telkom">
                        <i class="fas fa-plus"></i>
                        Tambah Admin
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Admin</th>
                            <th>NIK</th>
                            <th>Lantai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($admins)): 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_admin']) ?></td>
                            <td><?= htmlspecialchars($row['nik_admin']) ?></td>
                            <td><?= htmlspecialchars($row['lantai']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit=<?= $row['id_admin'] ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>
                                    <a href="?delete=<?= $row['id_admin'] ?>" class="btn-action btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus admin ini?')">
                                        <i class="fas fa-trash"></i>
                                        Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Form Section -->
        <?php if (isset($_GET['edit'])): 
            $id = $_GET['edit'];
            $e = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_admin WHERE id_admin=$id"));
        ?>
        <div class="form-section">
            <h3 class="table-title">
                <i class="fas fa-edit"></i>
                Edit Admin
            </h3>
            
            <form method="POST">
                <input type="hidden" name="id_admin" value="<?= $e['id_admin'] ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Nama Admin</label>
                        <input type="text" name="nama_admin" class="form-control" value="<?= htmlspecialchars($e['nama_admin']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">NIK Admin</label>
                        <input type="text" name="nik_admin" class="form-control" value="<?= htmlspecialchars($e['nik_admin']) ?>" required>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Lantai</label>
                        <select name="lantai" class="form-select" required>
                            <option value="1" <?= ($e['lantai']=="1" ? "selected" : "") ?>>Lantai 1</option>
                            <option value="2" <?= ($e['lantai']=="2" ? "selected" : "") ?>>Lantai 2</option>
                            <option value="3" <?= ($e['lantai']=="3" ? "selected" : "") ?>>Lantai 3</option>
                            <option value="5" <?= ($e['lantai']=="5" ? "selected" : "") ?>>Lantai 5</option>
                            <option value="6" <?= ($e['lantai']=="6" ? "selected" : "") ?>>Lantai 6</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password Baru (opsional)</label>
                        <input type="password" name="password_admin" class="form-control" placeholder="Kosongkan jika tidak diganti">
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="update" class="btn-telkom">
                        <i class="fas fa-save"></i>
                        Update Admin
                    </button>
                    <a href="data_admin.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-times"></i>
                        Batal
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
