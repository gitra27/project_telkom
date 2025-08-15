<?php
session_start();
if (!isset($_SESSION['status_user']) || $_SESSION['status_user'] !== 'login') {
  header("Location: login.php");
  exit;
}

$koneksi = mysqli_connect("localhost", "root", "", "db_bengkel1");
if (!$koneksi) {
  die("Koneksi gagal: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];

$query = "
  SELECT 
    b.bookings_id,
    b.booking_date,
    s.nama_service,
    s.harga,
    b.status
  FROM bookings b
  LEFT JOIN service s ON b.id_service = s.id_service
  WHERE b.user_id = '$user_id'
  ORDER BY b.booking_date DESC
";

$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Booking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <style>
    body {
      background-color: #1b1b1b;
      color: white;
    }
    .navbar {
      background-color: #1b1b1b;
    }
    .navbar-brand img {
      height: 40px;
    }
    .footer {
      background-color: #1b1b1b;
      color: #ccc;
      padding: 20px 0;
      text-align: center;
    }
    .badge-menunggu { background-color: orange; }
    .badge-diproses { background-color: blue; }
    .badge-selesai { background-color: green; }
    .badge-batal { background-color: red; }
  </style>
</head>
<body>

<!-- Navbar (disamakan dengan index.php) -->
<nav class="navbar navbar-expand-lg navbar-dark px-4">
  <a class="navbar-brand" href="index.php">
    <img src="logo1.png" alt="Logo">
  </a>
  <div class="ms-auto d-flex align-items-center">
    <?php if (isset($_SESSION['status_user']) && $_SESSION['status_user'] === 'login'): ?>
      <div class="dropdown me-3">
        <a href="#" class="text-white text-decoration-none dropdown-toggle d-flex align-items-center" id="userDropdown" role="button">
          <img src="profile.png" alt="Profile" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-right: 8px;">
          <?= htmlspecialchars($_SESSION['username']) ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end border-0 mt-2" style="background-color: transparent; backdrop-filter: blur(6px);">
          <li>
            <a class="dropdown-item text-white bg-transparent" href="riwayat_booking.php">
              <i class="ri-time-line me-2"></i> Riwayat Booking
            </a>
          </li>
        </ul>
      </div>
      <form action="logout.php" method="POST" class="m-0">
        <button type="submit" class="btn btn-primary">Logout</button>
      </form>
    <?php else: ?>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
        Login
      </button>
    <?php endif; ?>
  </div>
</nav>

<!-- Konten Riwayat Booking -->
<div class="container mt-5 mb-5">
  <h2 class="mb-4 text-center text-info">Riwayat Booking Saya</h2>
  <div class="table-responsive">
    <table class="table table-bordered table-hover table-dark">
      <thead>
        <tr>
          <th>No</th>
          <th>Tanggal Booking</th>
          <th>Nama Service</th>
          <th>Harga</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
          $badgeClass = match ($row['status']) {
            'Diproses' => 'badge-diproses',
            'Selesai' => 'badge-selesai',
            'Batal' => 'badge-batal',
            default => 'badge-menunggu',
          };
          echo "<tr>
            <td>{$no}</td>
            <td>{$row['booking_date']}</td>
            <td>" . ($row['nama_service'] ?? '-') . "</td>
            <td>" . ($row['harga'] ? 'Rp ' . number_format($row['harga'], 0, ',', '.') : '-') . "</td>
            <td><span class='badge {$badgeClass}'>{$row['status']}</span></td>
          </tr>";
          $no++;
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Footer (disamakan dengan index.php) -->
<footer class="footer">
  <p>&copy; <?= date('Y') ?> BengkelTune. All rights reserved.</p>
</footer>

</body>
</html>
