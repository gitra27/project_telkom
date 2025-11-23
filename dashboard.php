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
        $_SESSION['jabatan'] = $karyawan['jabatan'] ?? '';
        $_SESSION['departemen'] = $karyawan['departemen'] ?? '';
    } else {
        // Jika data tidak ditemukan, logout
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

$nik = $_SESSION['nik'] ?? '';
$nama = $_SESSION['nama'] ?? 'User';
$jabatan = $_SESSION['jabatan'] ?? '-';
$departemen = $_SESSION['departemen'] ?? '-';

// Cek absensi hari ini
$tanggal = date("Y-m-d");
$absen = null;
if (!empty($nik)) {
    $res = $conn->query("SELECT * FROM tb_absensi WHERE nik='$nik' AND tanggal='$tanggal'");
    if ($res) {
        $absen = $res->fetch_assoc();
    }
}

// Statistik bulan ini
$bulan_ini = date("Y-m");
$stat_bulan = [
    'total_hari' => 0,
    'hari_hadir' => 0,
    'hari_lengkap' => 0,
    'hari_terlambat' => 0,
    'hari_tidak_hadir' => 0
];

if (!empty($nik)) {
    $stat_result = $conn->query("SELECT 
        COUNT(*) as total_hari,
        COUNT(CASE WHEN jam_masuk IS NOT NULL THEN 1 END) as hari_hadir,
        COUNT(CASE WHEN jam_pulang IS NOT NULL THEN 1 END) as hari_lengkap,
        COUNT(CASE WHEN status = 'terlambat' THEN 1 END) as hari_terlambat,
        COUNT(CASE WHEN jam_masuk IS NULL THEN 1 END) as hari_tidak_hadir
        FROM tb_absensi 
        WHERE nik='$nik' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'");
    if ($stat_result) {
        $stat_bulan = $stat_result->fetch_assoc() ?: $stat_bulan;
    }
}

// Data 7 hari terakhir untuk grafik
$hari_terakhir = [];
if (!empty($nik)) {
    $hari_result = $conn->query("SELECT tanggal, jam_masuk, jam_pulang, status 
        FROM tb_absensi 
        WHERE nik='$nik' 
        ORDER BY tanggal DESC 
        LIMIT 7");
    if ($hari_result) {
        $hari_terakhir = $hari_result->fetch_all(MYSQLI_ASSOC) ?: [];
    }
}

// Data bulan ini untuk calendar
$data_bulan = [];
if (!empty($nik)) {
    $bulan_result = $conn->query("SELECT tanggal, jam_masuk, jam_pulang, status 
        FROM tb_absensi 
        WHERE nik='$nik' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'
        ORDER BY tanggal");
    if ($bulan_result) {
        $data_bulan = $bulan_result->fetch_all(MYSQLI_ASSOC) ?: [];
    }
}

// Hitung durasi kerja bulan ini
$total_durasi = 0;
foreach($data_bulan as $d) {
    if($d['jam_masuk'] && $d['jam_pulang']) {
        $masuk = strtotime($d['jam_masuk']);
        $pulang = strtotime($d['jam_pulang']);
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
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
              <p class="text-muted mb-0">
                <i class="fas fa-calendar me-2"></i><?= date("l, d F Y"); ?>
              </p>
            </div>
            <div class="col-md-4 text-md-end">
              <div class="clock-display">
                <i class="fas fa-clock me-2"></i>
                <span id="jam" class="fw-bold fs-4"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistik Bulan Ini -->
      <div class="row mb-4">
        <div class="col-md-3 mb-3">
          <div class="card shadow-sm border-0 bg-gradient-primary text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-white-50 mb-1">Total Hari</h6>
                  <h3 class="mb-0"><?= $stat_bulan['total_hari'] ?? 0; ?></h3>
                </div>
                <div class="stat-icon">
                  <i class="fas fa-calendar-alt fa-2x opacity-50"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card shadow-sm border-0 bg-gradient-success text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-white-50 mb-1">Hari Hadir</h6>
                  <h3 class="mb-0"><?= $stat_bulan['hari_hadir'] ?? 0; ?></h3>
                </div>
                <div class="stat-icon">
                  <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card shadow-sm border-0 bg-gradient-warning text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-white-50 mb-1">Terlambat</h6>
                  <h3 class="mb-0"><?= $stat_bulan['hari_terlambat'] ?? 0; ?></h3>
                </div>
                <div class="stat-icon">
                  <i class="fas fa-clock fa-2x opacity-50"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card shadow-sm border-0 bg-gradient-info text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-white-50 mb-1">Total Jam</h6>
                  <h3 class="mb-0"><?= $total_jam; ?>j</h3>
                </div>
                <div class="stat-icon">
                  <i class="fas fa-hourglass-half fa-2x opacity-50"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Absensi Hari Ini -->
        <div class="col-lg-8 mb-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
              <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Absensi Hari Ini</h5>
            </div>
            <div class="card-body">
              <?php if (!$absen): ?>
                <!-- Belum Absen Masuk -->
                <div class="text-center py-5">
                  <div class="absen-icon mb-4">
                    <div class="absen-circle bg-warning text-white mx-auto mb-3">
                      <i class="fas fa-clock fa-3x"></i>
                    </div>
                  </div>
                  <h4 class="mb-2 fw-bold">Belum Absen Masuk</h4>
                  <p class="text-muted mb-4">Silakan lakukan absen masuk untuk memulai hari kerja Anda</p>
                  
                  <!-- Informasi Waktu -->
                  <div class="absen-info-box mb-4">
                    <div class="row text-center">
                      <div class="col-md-4 mb-3">
                        <div class="info-item-box">
                          <i class="fas fa-calendar-alt text-primary mb-2"></i>
                          <p class="mb-1 text-muted small">Tanggal</p>
                          <p class="mb-0 fw-bold"><?= date("d F Y"); ?></p>
                        </div>
                      </div>
                      <div class="col-md-4 mb-3">
                        <div class="info-item-box">
                          <i class="fas fa-clock text-primary mb-2"></i>
                          <p class="mb-1 text-muted small">Waktu Sekarang</p>
                          <p class="mb-0 fw-bold" id="current-time"><?= date("H:i:s"); ?></p>
                        </div>
                      </div>
                      <div class="col-md-4 mb-3">
                        <div class="info-item-box">
                          <i class="fas fa-user text-primary mb-2"></i>
                          <p class="mb-1 text-muted small">Karyawan</p>
                          <p class="mb-0 fw-bold"><?= $nama; ?></p>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <form method="post" action="absen.php" id="form-checkin" onsubmit="return confirmAbsen('masuk')">
                    <input type="hidden" name="aksi" value="masuk">
                    <button type="submit" class="btn btn-primary btn-lg px-5 py-3 absen-btn">
                      <i class="fas fa-sign-in-alt me-2"></i>Check In Sekarang
                    </button>
                  </form>
                  <p class="text-muted small mt-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Pastikan Anda sudah berada di lokasi kerja sebelum melakukan check in
                  </p>
                </div>
              <?php elseif ($absen && !$absen['jam_pulang']): ?>
                <!-- Sudah Absen Masuk, Belum Pulang -->
                <div class="text-center py-5">
                  <div class="absen-icon mb-4">
                    <div class="absen-circle bg-success text-white mx-auto mb-3">
                      <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                  </div>
                  <h4 class="mb-2 fw-bold text-success">Sudah Absen Masuk</h4>
                  
                  <!-- Informasi Absen Masuk -->
                  <div class="absen-info-box mb-4">
                    <div class="row text-center">
                      <div class="col-md-6 mb-3">
                        <div class="info-item-box border-success">
                          <i class="fas fa-sign-in-alt text-success mb-2"></i>
                          <p class="mb-1 text-muted small">Jam Masuk</p>
                          <h3 class="mb-0 fw-bold text-success"><?= $absen['jam_masuk']; ?></h3>
                        </div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <div class="info-item-box">
                          <i class="fas fa-clock text-primary mb-2"></i>
                          <p class="mb-1 text-muted small">Waktu Sekarang</p>
                          <h3 class="mb-0 fw-bold" id="current-time-out"><?= date("H:i:s"); ?></h3>
                        </div>
                      </div>
                    </div>
                    <?php
                    // Hitung durasi kerja saat ini
                    $masuk = strtotime($absen['jam_masuk']);
                    $sekarang = time();
                    $durasi = $sekarang - $masuk;
                    $jam = floor($durasi / 3600);
                    $menit = floor(($durasi % 3600) / 60);
                    ?>
                    <div class="alert alert-info mt-3">
                      <i class="fas fa-hourglass-half me-2"></i>
                      Durasi kerja saat ini: <strong><?= $jam; ?> jam <?= $menit; ?> menit</strong>
                    </div>
                  </div>
                  
                  <p class="text-muted mb-4">Silakan lakukan absen pulang setelah selesai bekerja</p>
                  <form method="post" action="absen.php" id="form-checkout" onsubmit="return confirmAbsen('pulang')">
                    <input type="hidden" name="aksi" value="pulang">
                    <button type="submit" class="btn btn-danger btn-lg px-5 py-3 absen-btn">
                      <i class="fas fa-sign-out-alt me-2"></i>Check Out Sekarang
                    </button>
                  </form>
                </div>
              <?php else: ?>
                <!-- Absensi Selesai -->
                <div class="text-center py-5">
                  <div class="absen-icon mb-4">
                    <div class="absen-circle bg-success text-white mx-auto mb-3">
                      <i class="fas fa-check-double fa-3x"></i>
                    </div>
                  </div>
                  <h4 class="mb-2 fw-bold text-success">Absensi Selesai</h4>
                  <p class="text-muted mb-4">Terima kasih! Absensi hari ini sudah selesai.</p>
                  
                  <!-- Informasi Lengkap -->
                  <div class="absen-info-box mb-4">
                    <div class="row text-center">
                      <div class="col-md-6 mb-3">
                        <div class="info-item-box border-success">
                          <i class="fas fa-sign-in-alt text-success mb-2"></i>
                          <p class="mb-1 text-muted small">Jam Masuk</p>
                          <h4 class="mb-0 fw-bold text-success"><?= $absen['jam_masuk']; ?></h4>
                        </div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <div class="info-item-box border-danger">
                          <i class="fas fa-sign-out-alt text-danger mb-2"></i>
                          <p class="mb-1 text-muted small">Jam Pulang</p>
                          <h4 class="mb-0 fw-bold text-danger"><?= $absen['jam_pulang']; ?></h4>
                        </div>
                      </div>
                    </div>
                    <?php
                    if($absen['jam_masuk'] && $absen['jam_pulang']) {
                      $masuk = strtotime($absen['jam_masuk']);
                      $pulang = strtotime($absen['jam_pulang']);
                      $durasi = $pulang - $masuk;
                      $jam = floor($durasi / 3600);
                      $menit = floor(($durasi % 3600) / 60);
                    ?>
                    <div class="alert alert-success mt-3">
                      <i class="fas fa-clock me-2"></i>
                      <strong>Durasi kerja hari ini: <?= $jam; ?> jam <?= $menit; ?> menit</strong>
                    </div>
                    <?php } ?>
                  </div>
                  
                  <div class="alert alert-info">
                    <i class="fas fa-check-circle me-2"></i>
                    Absensi Anda telah tercatat dengan baik. Selamat beristirahat!
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Grafik Kehadiran 7 Hari Terakhir -->
        <div class="col-lg-4 mb-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
              <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Grafik Kehadiran 7 Hari Terakhir</h5>
            </div>
            <div class="card-body">
              <canvas id="kehadiranChart" height="250"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Calendar & Quick Actions -->
      <div class="row">
        <!-- Calendar View -->
        <div class="col-lg-8 mb-4">
          <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Kalender Absensi Bulan Ini</h5>
              <span class="badge bg-primary"><?= date("F Y"); ?></span>
            </div>
            <div class="card-body">
              <div class="calendar-container">
                <div class="calendar-grid">
                  <?php
                  $first_day = date("Y-m-01");
                  $last_day = date("Y-m-t");
                  $start_date = strtotime($first_day);
                  $end_date = strtotime($last_day);
                  $current = $start_date;
                  
                  // Buat array data absensi untuk lookup
                  $absen_lookup = [];
                  foreach($data_bulan as $d) {
                    $absen_lookup[$d['tanggal']] = $d;
                  }
                  
                  // Header hari
                  $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                  echo '<div class="calendar-header">';
                  foreach($days as $day) {
                    echo '<div class="calendar-day-header">' . substr($day, 0, 3) . '</div>';
                  }
                  echo '</div>';
                  
                  // Spacer untuk hari pertama
                  $first_day_of_week = date('w', $start_date);
                  echo '<div class="calendar-week">';
                  for($i = 0; $i < $first_day_of_week; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                  }
                  
                  // Tanggal-tanggal
                  while($current <= $end_date) {
                    $date_str = date("Y-m-d", $current);
                    $day = date("d", $current);
                    $is_today = ($date_str == $tanggal);
                    $absen_data = $absen_lookup[$date_str] ?? null;
                    
                    $class = "calendar-day";
                    if($is_today) $class .= " today";
                    if($absen_data) {
                      if($absen_data['jam_pulang']) $class .= " complete";
                      elseif($absen_data['jam_masuk']) $class .= " partial";
                      else $class .= " absent";
                    }
                    
                    echo '<div class="' . $class . '">';
                    echo '<div class="calendar-day-number">' . $day . '</div>';
                    if($absen_data) {
                      if($absen_data['jam_masuk']) {
                        echo '<div class="calendar-status"><i class="fas fa-check text-success"></i></div>';
                      }
                    }
                    echo '</div>';
                    
                    if(date('w', $current) == 6) {
                      echo '</div><div class="calendar-week">';
                    }
                    
                    $current = strtotime("+1 day", $current);
                  }
                  
                  // Spacer untuk sisa minggu
                  $last_day_of_week = date('w', $end_date);
                  for($i = $last_day_of_week + 1; $i < 7; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                  }
                  echo '</div>';
                  ?>
                </div>
                <div class="calendar-legend mt-3">
                  <span class="legend-item"><span class="legend-color today"></span> Hari Ini</span>
                  <span class="legend-item"><span class="legend-color complete"></span> Lengkap</span>
                  <span class="legend-item"><span class="legend-color partial"></span> Belum Pulang</span>
                  <span class="legend-item"><span class="legend-color absent"></span> Tidak Hadir</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions & Info -->
        <div class="col-lg-4 mb-4">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-white">
              <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
              <a href="riwayat_absen.php" class="btn btn-outline-primary w-100 mb-2">
                <i class="fas fa-history me-2"></i>Riwayat Absensi
              </a>
              <button class="btn btn-outline-success w-100 mb-2" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Cetak Laporan
              </button>
              <button class="btn btn-outline-info w-100" data-bs-toggle="modal" data-bs-target="#infoModal">
                <i class="fas fa-info-circle me-2"></i>Info Sistem
              </button>
            </div>
          </div>

          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Informasi Karyawan</h5>
            </div>
            <div class="card-body">
              <div class="info-row mb-3">
                <small class="text-muted d-block">NIK</small>
                <strong><?= $nik; ?></strong>
              </div>
              <div class="info-row mb-3">
                <small class="text-muted d-block">Jabatan</small>
                <strong><?= $jabatan; ?></strong>
              </div>
              <div class="info-row mb-3">
                <small class="text-muted d-block">Departemen</small>
                <strong><?= $departemen; ?></strong>
              </div>
              <div class="info-row">
                <small class="text-muted d-block">Status</small>
                <span class="badge bg-success">Aktif</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Info Modal -->
<div class="modal fade" id="infoModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Informasi Sistem</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Jam Kerja:</strong> 08:00 - 17:00 WIB</p>
        <p><strong>Batas Terlambat:</strong> 08:15 WIB</p>
        <p><strong>Durasi Kerja:</strong> 8 jam per hari</p>
        <hr>
        <p class="text-muted small mb-0">
          <i class="fas fa-shield-alt me-1"></i>
          Sistem Absensi Karyawan TelkomAkses v1.0
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
 <script>
 // Update Jam Real-time
 function updateJam() {
     const sekarang = new Date();
     const jam = sekarang.getHours().toString().padStart(2, '0');
     const menit = sekarang.getMinutes().toString().padStart(2, '0');
     const detik = sekarang.getSeconds().toString().padStart(2, '0');
     const timeString = jam + ':' + menit + ':' + detik;
     
     // Update jam di header
     if(document.getElementById('jam')) {
         document.getElementById('jam').textContent = timeString;
     }
     
     // Update waktu saat ini di form absen
     const currentTimeEl = document.getElementById('current-time');
     const currentTimeOutEl = document.getElementById('current-time-out');
     if(currentTimeEl) {
         currentTimeEl.textContent = timeString;
     }
     if(currentTimeOutEl) {
         currentTimeOutEl.textContent = timeString;
     }
 }
 setInterval(updateJam, 1000);
 updateJam();
 
 // Konfirmasi sebelum absen
 function confirmAbsen(aksi) {
     const actionText = aksi === 'masuk' ? 'Check In' : 'Check Out';
     const waktu = new Date().toLocaleTimeString('id-ID');
     return confirm(`Apakah Anda yakin ingin melakukan ${actionText} pada pukul ${waktu}?`);
 }
 </script>

// Grafik Kehadiran
const ctx = document.getElementById('kehadiranChart').getContext('2d');
const kehadiranData = <?= json_encode(array_reverse($hari_terakhir)); ?>;
let labels = [];
let hadirData = [];
let pulangData = [];

if(kehadiranData.length > 0) {
    labels = kehadiranData.map(d => {
        const date = new Date(d.tanggal);
        return date.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' });
    });
    hadirData = kehadiranData.map(d => d.jam_masuk ? 1 : 0);
    pulangData = kehadiranData.map(d => d.jam_pulang ? 1 : 0);
} else {
    // Jika tidak ada data, tampilkan 7 hari terakhir kosong
    for(let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        labels.push(date.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' }));
        hadirData.push(0);
        pulangData.push(0);
    }
}

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Hadir',
            data: hadirData,
            borderColor: 'rgb(40, 167, 69)',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Lengkap',
            data: pulangData,
            borderColor: 'rgb(220, 53, 69)',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 1,
                ticks: {
                    stepSize: 1,
                    callback: function(value) {
                        return value === 1 ? 'Ya' : 'Tidak';
                    }
                }
            }
        }
    }
});
</script>
</body>
</html>