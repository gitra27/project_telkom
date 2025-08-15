<?php
session_start();
include 'koneksi.php'; // pastikan file koneksi ini benar

// Cek apakah user sudah login
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Ambil email dari session
$email = $_SESSION['email'];

// Ambil data user berdasarkan email
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
$user_data = mysqli_fetch_assoc($user_query);

// Cek jika user ditemukan
if (!$user_data) {
    echo "User tidak ditemukan.";
    exit;
}

// Ambil id_user untuk mengambil booking
$id_user = $user_data['id_user'];

// Ambil semua data booking dari user
$booking_query = mysqli_query($conn, "SELECT * FROM bookings WHERE id_user = '$id_user' ORDER BY tanggal_booking DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Pengguna - BengkelTune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1b1b1b;
            color: white;
        }
        .card {
            background-color: #2a2a2a;
            color: white;
            border: none;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">Profil Pengguna</h2>

    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Nama Lengkap:</strong> <?= $user_data['nama_lengkap'] ?></p>
            <p><strong>Email:</strong> <?= $user_data['email'] ?></p>
            <p><strong>No. HP:</strong> <?= $user_data['no_hp'] ?></p>
        </div>
    </div>

    <h4>Riwayat Booking</h4>
    <?php if (mysqli_num_rows($booking_query) > 0): ?>
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal Booking</th>
                    <th>Layanan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($booking = mysqli_fetch_assoc($booking_query)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $booking['tanggal_booking'] ?></td>
                    <td><?= $booking['layanan'] ?></td>
                    <td><?= $booking['status_booking'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Belum ada booking yang dilakukan.</p>
    <?php endif; ?>
</div>
</body>
</html>
