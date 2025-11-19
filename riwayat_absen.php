<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nik = $_SESSION['nik'];
$nama = $_SESSION['nama'];

// Ambil riwayat absensi
$res = $conn->query("SELECT * FROM tb_absensi WHERE nik='$nik' ORDER BY tanggal DESC LIMIT 30");
$riwayat = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <title>Riwayat Absensi - TelkomAkses</title>
</head>
<body class="dashboard-body">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
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
          <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <!-- Page Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1"><i class="fas fa-history me-2"></i>Riwayat Absensi</h2>
          <p class="text-muted mb-0">Data kehadiran <?= $nama; ?></p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-primary">
          <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
        </a>
      </div>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card bg-primary text-white">
            <div class="card-body text-center">
              <i class="fas fa-calendar-check fa-2x mb-2"></i>
              <h5 class="mb-1"><?= count($riwayat); ?></h5>
              <small>Total Hari</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-success text-white">
            <div class="card-body text-center">
              <i class="fas fa-check-circle fa-2x mb-2"></i>
              <h5 class="mb-1"><?= count(array_filter($riwayat, function($r) { return $r['jam_pulang']; })); ?></h5>
              <small>Hari Lengkap</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-warning text-white">
            <div class="card-body text-center">
              <i class="fas fa-clock fa-2x mb-2"></i>
              <h5 class="mb-1"><?= count(array_filter($riwayat, function($r) { return $r['jam_masuk'] && !$r['jam_pulang']; })); ?></h5>
              <small>Belum Pulang</small>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-danger text-white">
            <div class="card-body text-center">
              <i class="fas fa-times-circle fa-2x mb-2"></i>
              <h5 class="mb-1"><?= count(array_filter($riwayat, function($r) { return !$r['jam_masuk']; })); ?></h5>
              <small>Tidak Hadir</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h5 class="mb-0"><i class="fas fa-table me-2"></i>Data Absensi 30 Hari Terakhir</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th><i class="fas fa-calendar me-1"></i>Tanggal</th>
                  <th><i class="fas fa-sign-in-alt me-1"></i>Jam Masuk</th>
                  <th><i class="fas fa-sign-out-alt me-1"></i>Jam Pulang</th>
                  <th><i class="fas fa-info-circle me-1"></i>Status</th>
                  <th><i class="fas fa-clock me-1"></i>Durasi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($riwayat)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-4">
                      <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                      <p class="text-muted mb-0">Belum ada data absensi</p>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($riwayat as $row): ?>
                    <tr>
                      <td>
                        <div>
                          <strong><?= date("d M Y", strtotime($row['tanggal'])); ?></strong>
                          <br>
                          <small class="text-muted"><?= date("l", strtotime($row['tanggal'])); ?></small>
                        </div>
                      </td>
                      <td>
                        <?php if ($row['jam_masuk']): ?>
                          <span class="badge bg-success fs-6"><?= $row['jam_masuk']; ?></span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($row['jam_pulang']): ?>
                          <span class="badge bg-danger fs-6"><?= $row['jam_pulang']; ?></span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($row['jam_pulang']): ?>
                          <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>Selesai
                          </span>
                        <?php elseif ($row['jam_masuk']): ?>
                          <span class="badge bg-warning">
                            <i class="fas fa-clock me-1"></i>Belum Pulang
                          </span>
                        <?php else: ?>
                          <span class="badge bg-danger">
                            <i class="fas fa-times me-1"></i>Tidak Hadir
                          </span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($row['jam_masuk'] && $row['jam_pulang']): ?>
                          <?php
                          $masuk = strtotime($row['jam_masuk']);
                          $pulang = strtotime($row['jam_pulang']);
                          $durasi = $pulang - $masuk;
                          $jam = floor($durasi / 3600);
                          $menit = floor(($durasi % 3600) / 60);
                          ?>
                          <span class="text-success fw-semibold">
                            <?= $jam; ?>j <?= $menit; ?>m
                          </span>
                        <?php elseif ($row['jam_masuk']): ?>
                          <span class="text-warning">
                            <i class="fas fa-clock me-1"></i>Berlangsung
                          </span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Export Button -->
<div class="position-fixed bottom-0 end-0 p-3">
  <button class="btn btn-primary btn-lg rounded-circle shadow" data-bs-toggle="tooltip" title="Export Data">
    <i class="fas fa-download"></i>
  </button>
</div>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})
</script>
</body>
</html>
