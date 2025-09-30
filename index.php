<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
<<<<<<< HEAD
    exit;
=======
    exit; 123
>>>>>>> 9af60685e15da153edce8c8ae3426ef4205d070f
}

$user_id = $_SESSION['user_id'];
$nama    = $_SESSION['nama']; 

// Cek absensi hari ini
$tanggal = date("Y-m-d");
$res = $conn->query("SELECT * FROM tb_absensi WHERE user_id='$user_id' AND tanggal='$tanggal'");
$absen = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="style.css">
  <title>Absensi Karyawan</title>
</head>
<body class="bg-white text-white">
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6 text-center">
    <div class="text-center my-3">
  <img src="telkom.png" class="img-fluid" style="max-height:200px; max-width:250px;" alt="Logo Telkom">
</div>
      <div class="card shadow-lg rounded-4">
        <div class="card-header text-center">
          <h2 class="mb-0">Absensi Karyawan</h2>
          <h4 class="mb-0">TelkomAkses</h4>
        </div>
        <div class="card-body text-center">
          <h5 class="card-title">Hai, <?= $nama; ?></h5>
          <p class="card-text">Tanggal: <?= date("d M Y"); ?></p>

          <?php if (!$absen): ?>
            <form method="post" action="absen.php">
              <input type="hidden" name="aksi" value="masuk">
              <button class="btn btn-success btn-lg w-100">Check In</button>
            </form>
          <?php elseif ($absen && !$absen['jam_pulang']): ?>
            <form method="post" action="absen.php">
              <input type="hidden" name="aksi" value="pulang">
              <button class="btn btn-danger btn-lg w-100">Check Out</button>
            </form>
          <?php else: ?>
            <div class="alert alert-info d-flex justify-content-between align-items-center mt-3" role="alert">
              Hari ini absen sudah selesai. meuheueuhehue 
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
        </div>
        <div class="card-footer text-center">
          <a href="logout.php" class="btn btn-primary btn-lg w-100">Logout</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
