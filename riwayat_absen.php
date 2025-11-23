<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data karyawan dari database jika session tidak lengkap
if (!isset($_SESSION['nik']) || !isset($_SESSION['nama'])) {
    $karyawan_result = $conn->query("SELECT * FROM tb_karyawan WHERE id='$user_id'");
    if ($karyawan_result && $karyawan_result->num_rows > 0) {
        $karyawan = $karyawan_result->fetch_assoc();
        $_SESSION['nik'] = $karyawan['nik'];
        $_SESSION['nama'] = $karyawan['nama'];
    } else {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

$nik = $_SESSION['nik'] ?? '';
$nama = $_SESSION['nama'] ?? 'User';

// Filter bulan dan tahun
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$filter_bulan = date('Y-m', strtotime($bulan));

// Ambil riwayat absensi dengan filter
$riwayat = [];
if (!empty($nik)) {
    $res = $conn->query("SELECT * FROM tb_absensi 
        WHERE nik='$nik' 
        AND DATE_FORMAT(tanggal, '%Y-%m') = '$filter_bulan'
        ORDER BY tanggal DESC");
    if ($res) {
        $riwayat = $res->fetch_all(MYSQLI_ASSOC) ?: [];
    }
}

// Hitung statistik
$total_hari = count($riwayat);
$hari_hadir = count(array_filter($riwayat, function($r) { return $r['jam_masuk']; }));
$hari_lengkap = count(array_filter($riwayat, function($r) { return $r['jam_pulang']; }));
$hari_terlambat = count(array_filter($riwayat, function($r) { return $r['status'] == 'terlambat'; }));
$hari_tidak_hadir = count(array_filter($riwayat, function($r) { return !$r['jam_masuk']; }));

// Hitung total durasi
$total_durasi = 0;
foreach($riwayat as $r) {
    if($r['jam_masuk'] && $r['jam_pulang']) {
        $masuk = strtotime($r['jam_masuk']);
        $pulang = strtotime($r['jam_pulang']);
        $total_durasi += ($pulang - $masuk);
    }
}
$total_jam = floor($total_durasi / 3600);
$total_menit = floor(($total_durasi % 3600) / 60);
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
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
          <h2 class="mb-1"><i class="fas fa-history me-2"></i>Riwayat Absensi</h2>
          <p class="text-muted mb-0">Data kehadiran <?= $nama; ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <form method="get" action="" class="d-flex gap-2">
            <input type="month" name="bulan" class="form-control" value="<?= $filter_bulan; ?>" onchange="this.form.submit()">
          </form>
          <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Dashboard
          </a>
          <button class="btn btn-primary" onclick="exportToCSV()">
            <i class="fas fa-download me-2"></i>Export CSV
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-md-2 col-6 mb-3">
          <div class="card bg-gradient-primary text-white shadow-sm">
            <div class="card-body text-center">
              <i class="fas fa-calendar-check fa-2x mb-2 opacity-75"></i>
              <h4 class="mb-1"><?= $total_hari; ?></h4>
              <small class="opacity-75">Total Hari</small>
            </div>
          </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
          <div class="card bg-gradient-success text-white shadow-sm">
            <div class="card-body text-center">
              <i class="fas fa-check-circle fa-2x mb-2 opacity-75"></i>
              <h4 class="mb-1"><?= $hari_hadir; ?></h4>
              <small class="opacity-75">Hari Hadir</small>
            </div>
          </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
          <div class="card bg-gradient-info text-white shadow-sm">
            <div class="card-body text-center">
              <i class="fas fa-check-double fa-2x mb-2 opacity-75"></i>
              <h4 class="mb-1"><?= $hari_lengkap; ?></h4>
              <small class="opacity-75">Hari Lengkap</small>
            </div>
          </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
          <div class="card bg-gradient-warning text-white shadow-sm">
            <div class="card-body text-center">
              <i class="fas fa-clock fa-2x mb-2 opacity-75"></i>
              <h4 class="mb-1"><?= $hari_terlambat; ?></h4>
              <small class="opacity-75">Terlambat</small>
            </div>
          </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
          <div class="card bg-gradient-danger text-white shadow-sm">
            <div class="card-body text-center">
              <i class="fas fa-times-circle fa-2x mb-2 opacity-75"></i>
              <h4 class="mb-1"><?= $hari_tidak_hadir; ?></h4>
              <small class="opacity-75">Tidak Hadir</small>
            </div>
          </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
          <div class="card bg-gradient-secondary text-white shadow-sm">
            <div class="card-body text-center">
              <i class="fas fa-hourglass-half fa-2x mb-2 opacity-75"></i>
              <h4 class="mb-1"><?= $total_jam; ?>j</h4>
              <small class="opacity-75">Total Jam</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-table me-2"></i>Data Absensi <?= date("F Y", strtotime($filter_bulan . '-01')); ?></h5>
          <span class="badge bg-primary"><?= count($riwayat); ?> record</span>
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

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})

// Export to CSV
function exportToCSV() {
    const data = <?= json_encode($riwayat); ?>;
    const headers = ['Tanggal', 'Hari', 'Jam Masuk', 'Jam Pulang', 'Status', 'Durasi'];
    let csv = headers.join(',') + '\n';
    
    data.forEach(row => {
        const tanggal = new Date(row.tanggal);
        const hari = tanggal.toLocaleDateString('id-ID', { weekday: 'long' });
        const jam_masuk = row.jam_masuk || '-';
        const jam_pulang = row.jam_pulang || '-';
        let status = 'Tidak Hadir';
        if(row.jam_pulang) status = 'Selesai';
        else if(row.jam_masuk) status = 'Belum Pulang';
        
        let durasi = '-';
        if(row.jam_masuk && row.jam_pulang) {
            const masuk = new Date('2000-01-01 ' + row.jam_masuk);
            const pulang = new Date('2000-01-01 ' + row.jam_pulang);
            const diff = (pulang - masuk) / 1000 / 60; // dalam menit
            const jam = Math.floor(diff / 60);
            const menit = diff % 60;
            durasi = jam + 'j ' + menit + 'm';
        }
        
        csv += [
            row.tanggal,
            hari,
            jam_masuk,
            jam_pulang,
            status,
            durasi
        ].join(',') + '\n';
    });
    
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'riwayat_absen_<?= $filter_bulan; ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
</body>
</html>
