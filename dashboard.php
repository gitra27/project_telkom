<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nik = $_SESSION['nik'];
$nama = $_SESSION['nama']; 
$jabatan = $_SESSION['jabatan'];
$departemen = $_SESSION['departemen'];

// Cek absensi hari ini
$tanggal = date("Y-m-d");
$res = $conn->query("SELECT * FROM tb_absensi WHERE nik='$nik' AND tanggal='$tanggal'");
$absen = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <title>Dashboard Absensi - TelkomAkses</title>
</head>
<body class="dashboard-body">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="telkom.png" alt="Logo Telkom" height="40" class="me-2">
      <span class="fw-bold">Sistem Absensi Karyawan</span>
    </a>
    <div class="navbar-nav ms-auto">
      <div class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
          <i class="fas fa-user-circle me-2"></i>
          <?= $nama; ?>
        </a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="riwayat_absen.php"><i class="fas fa-history me-2"></i>Riwayat Absensi</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3 mb-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="text-center mb-3">
            <div class="avatar-circle bg-primary text-white mx-auto mb-3">
              <i class="fas fa-user fa-2x"></i>
            </div>
            <h5 class="mb-1"><?= $nama; ?></h5>
            <p class="text-muted mb-1"><?= $jabatan; ?></p>
            <small class="text-muted"><?= $departemen; ?></small>
          </div>
          <hr>
          <div class="info-item">
            <i class="fas fa-id-card text-primary me-2"></i>
            <span class="fw-semibold">NIK:</span>
            <span class="text-muted"><?= $nik; ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9">
      <!-- Welcome Card -->
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h4 class="mb-2">Selamat datang, <?= $nama; ?>!</h4>
              <p class="text-muted mb-0">Hari ini adalah <?= date("l, d F Y"); ?></p>
            </div>
            <div class="col-md-4 text-md-end">
              <div class="clock-display">
                <i class="fas fa-clock text-primary me-2"></i>
                <span id="jam" class="fw-bold fs-4"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Absensi Card -->
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Absensi Hari Ini</h5>
        </div>
          <?php if (!$absen): ?>
            <div class="text-center py-4">
              <i class="fas fa-clock text-warning fa-3x mb-3"></i>
              <h5 class="mb-3">Belum Absen Masuk</h5>
              <p class="text-muted mb-4">Silakan lakukan absen masuk untuk memulai hari kerja Anda</p>
              <form method="post" action="absen.php">
                <input type="hidden" name="aksi" value="masuk">
                <button class="btn btn-success btn-lg px-5">
                  <i class="fas fa-sign-in-alt me-2"></i>Check In
                </button>
              </form>
            </div>
          <?php elseif ($absen && !$absen['jam_pulang']): ?>
            <div class="text-center py-4">
              <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
              <h5 class="mb-3">Sudah Absen Masuk</h5>
              <p class="text-muted mb-2">Jam Masuk: <strong><?= $absen['jam_masuk']; ?></strong></p>
              <p class="text-muted mb-4">Silakan lakukan absen pulang setelah selesai bekerja</p>
              <form method="post" action="absen.php">
                <input type="hidden" name="aksi" value="pulang">
                <button class="btn btn-danger btn-lg px-5">
                  <i class="fas fa-sign-out-alt me-2"></i>Check Out
                </button>
              </form>
            </div>
          <?php else: ?>
            <div class="text-center py-4">
              <i class="fas fa-check-double text-success fa-3x mb-3"></i>
              <h5 class="mb-3">Absensi Selesai</h5>
              <div class="row text-center">
                <div class="col-md-6">
                  <p class="text-muted mb-1">Jam Masuk</p>
                  <h6 class="fw-bold text-success"><?= $absen['jam_masuk']; ?></h6>
                </div>
                <div class="col-md-6">
                  <p class="text-muted mb-1">Jam Pulang</p>
                  <h6 class="fw-bold text-danger"><?= $absen['jam_pulang']; ?></h6>
                </div>
              </div>
              <div class="alert alert-success mt-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Terima kasih! Absensi hari ini sudah selesai.
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function updateJam() {
    const sekarang = new Date();
    const jam = sekarang.getHours().toString().padStart(2, '0');
    const menit = sekarang.getMinutes().toString().padStart(2, '0');
    const detik = sekarang.getSeconds().toString().padStart(2, '0');
    document.getElementById('jam').textContent = jam + ':' + menit + ':' + detik;
}

// Update jam setiap detik
setInterval(updateJam, 1000);
updateJam(); // Panggil sekali saat halaman dimuat
</script>
</body>
</html>