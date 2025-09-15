<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
$res = $conn->query("SELECT a.*, u.nama FROM tb_absensi a JOIN tb_users u ON a.user_id=u.id ORDER BY tanggal DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="assets/bootstrap.min.css">
  <title>Dashboard Admin</title>
</head>
<body class="bg-dark text-white">
<div class="container mt-4">
  <h3>📊 Dashboard Absensi</h3>
  <table class="table table-dark table-bordered mt-3">
    <thead>
      <tr>
        <th>Nama</th>
        <th>Tanggal</th>
        <th>Jam Masuk</th>
        <th>Jam Pulang</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?= $row['nama']; ?></td>
        <td><?= $row['tanggal']; ?></td>
        <td><?= $row['jam_masuk']; ?></td>
        <td><?= $row['jam_pulang'] ?: "-"; ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <a href="logout.php" class="btn btn-secondary">Logout</a>
</div>
</body>
</html>
