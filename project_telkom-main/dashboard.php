<?php
include "config.php";
session_start();
if(!isset($_SESSION['nik'])){ header("Location: login.php"); exit; }

$nik = $_SESSION['nik'];
$today = date('Y-m-d');

$q = mysqli_query($conn,"SELECT * FROM tb_absensi WHERE nik='$nik' AND tanggal='$today' LIMIT 1");
$absen = mysqli_fetch_assoc($q);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Dashboard Presensi</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet">

<style>
.page-body{background:#f5f7fb}

/* WARNA KHAS TELKOM */
.btn-telkom{
  background:#E11C2A;
  border:none;
  color:#fff;
}
.btn-telkom:hover{
  background:#c91622;
  color:#fff;
}
.btn-disabled{
  background:#adb5bd !important;
  color:#fff !important;
  cursor:not-allowed;
}
</style>
</head>

<body>
<div class="page">

<!-- NAVBAR -->
<header class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
  <div class="container-xl">
    <span class="navbar-brand fw-bold text-danger">Dashboard Presensi</span>
    <div class="navbar-nav ms-auto">
      <a class="nav-link active" href="#">Home</a>
      <a class="nav-link" href="rekap.php">Rekap Presensi</a>
      <a class="nav-link" href="ketidakhadiran.php">Ketidakhadiran</a>
      <a class="nav-link text-danger" href="logout.php">Logout</a>
    </div>
  </div>
</header>

<!-- CONTENT -->
<div class="page-body">
  <div class="container-xl">

    <h2 class="mb-4">Dashboard</h2>

    <div class="row row-cards">

      <!-- PRESENSI MASUK -->
      <div class="col-md-6">
        <div class="card text-center">
          <div class="card-body">
            <div class="text-muted">Presensi Masuk</div>
            <h4 class="mt-2"><?=date('d F Y')?></h4>
            <h1 class="my-3 fw-bold" id="jamMasuk"></h1>

            <form action="proses_absenmasuk.php" method="POST">
              <button
                class="btn <?= $absen ? 'btn-disabled' : 'btn-telkom' ?>"
                <?=($absen?'disabled':'')?>>
                Masuk
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- PRESENSI KELUAR -->
      <div class="col-md-6">
        <div class="card text-center">
          <div class="card-body">
            <div class="text-muted">Presensi Keluar</div>
            <h4 class="mt-2"><?=date('d F Y')?></h4>
            <h1 class="my-3 fw-bold" id="jamKeluar"></h1>

            <form action="proses_absenkeluar.php" method="POST">
              <button
                class="btn <?= (!$absen || $absen['jam_pulang']) ? 'btn-disabled' : 'btn-telkom' ?>"
                <?=(!$absen || $absen['jam_pulang']?'disabled':'')?>>
                Keluar
              </button>
            </form>
          </div>
        </div>
      </div>

    </div>

    <!-- RIWAYAT ABSENSI -->
    <div class="card mt-4">
      <div class="card-header">
        <h3 class="card-title">Riwayat Absensi</h3>
      </div>

      <div class="table-responsive">
        <table class="table card-table table-vcenter">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Masuk</th>
              <th>Pulang</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $riwayat = mysqli_query($conn,"
            SELECT * FROM tb_absensi
            WHERE nik='$nik'
            ORDER BY tanggal DESC
          ");
          while($r=mysqli_fetch_assoc($riwayat)):
          ?>
            <tr>
              <td><?=date('d-m-Y',strtotime($r['tanggal']))?></td>
              <td><?=$r['jam_masuk'] ?: '-'?></td>
              <td><?=$r['jam_pulang'] ?: '-'?></td>
              <td>
                <span class="badge
                  <?=($r['status']=='Hadir'?'bg-success':
                     ($r['status']=='Telat'?'bg-warning':
                     ($r['status']=='Sakit'?'bg-info':
                     ($r['status']=='Izin'?'bg-primary':'bg-secondary'))))?>">
                  <?=$r['status']?>
                </span>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

</div>

<script>
function updateJam(){
  const now = new Date().toLocaleTimeString('id-ID');
  document.getElementById('jamMasuk').innerText = now;
  document.getElementById('jamKeluar').innerText = now;
}
setInterval(updateJam,1000);updateJam();
</script>

</body>
</html>