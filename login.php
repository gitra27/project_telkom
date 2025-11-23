<?php 
include 'config.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="style.css" />
  <title>Login - Sistem Absensi Karyawan</title>
</head>
<body class="login-body">
<div class="container-fluid">
  <div class="row min-vh-100">

    <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center login-left">
      <div class="text-center">
        <img src="telkom.png" class="img-fluid mb-4" style="height: 100px; width: 250px;" alt="Logo Telkom">
        <h2 class="fw-bold mb-3 text-dark">Sistem Absensi Karyawan</h2>
        <h4 class="fw-light text-muted">TelkomAkses</h4>
        <p class="mt-4 fs-5 text-muted">Kelola kehadiran karyawan dengan mudah dan efisien</p>
      </div>
    </div>
    
    <div class="col-lg-6 d-flex align-items-center justify-content-center">
      <div class="login-form-container">
        <div class="text-center mb-4">
          <h3 class="fw-bold text-dark">Selamat Datang</h3>
          <p class="text-muted">Silakan login untuk mengakses sistem absensi</p>
        </div>
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nik = $_POST['nik'];
            $pass = $_POST['password'];
            
            // Debug: cek apakah data POST diterima
            if (empty($nik) || empty($pass)) {
                echo "<div class='alert alert-warning'>NIK dan password harus diisi!</div>";
            } else {
                $res = $conn->query("SELECT * FROM tb_karyawan WHERE nik='$nik'");
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    if (password_verify($pass, $row['password'])) {
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['nik']     = $row['nik'];
                        $_SESSION['nama']    = $row['nama'];
                        $_SESSION['jabatan'] = $row['jabatan'];
                        $_SESSION['departemen'] = $row['departemen'];
                        
                        // Pastikan tidak ada output sebelum header
                        ob_clean();
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        echo "<div class='alert alert-danger'>Password salah!</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>NIK tidak ditemukan!</div>";
                }
            }
        }
        ?>
        <form method="post" action="" class="login-form">
          <div class="mb-4">
            <label class="form-label fw-semibold">NIK Karyawan</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0">
                <i class="fas fa-id-card text-muted"></i>
              </span>
              <input type="text" name="nik" class="form-control border-start-0" placeholder="Masukkan NIK Anda" required>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0">
                <i class="fas fa-lock text-muted"></i>
              </span>
              <input type="password" name="password" class="form-control border-start-0" placeholder="Masukkan password Anda" required>
            </div>
          </div>
          <button class="btn btn-primary w-100 py-3 fw-semibold fs-5 d-flex justify-content-center align-items-center">
    <i class="fas fa-sign-in-alt me-2"></i>
    Masuk ke Sistem
</button>
        </form>
        
        <div class="text-center mt-4">
          <small class="text-muted">
            <i class="fas fa-shield-alt me-1"></i>
            Data Anda aman dan terlindungi
          </small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>
