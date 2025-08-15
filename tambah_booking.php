<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cek status login dan id_user
    if (!isset($_SESSION['status_user']) || $_SESSION['status_user'] !== 'login' || !isset($_SESSION['id_user'])) {
        header("Location: index.php?pesan=login_dulu");
        exit();
    }

    // Validasi input
    if (!isset($_POST['id_service']) || !isset($_POST['booking_date'])) {
        header("Location: index.php?pesan=data_tidak_lengkap");
        exit();
    }

    $user_id = $_SESSION['id_user'];
    $id_service = $_POST['id_service'];
    $date = $_POST['booking_date'];

    // Validasi data sebelum insert
    if (empty($user_id) || empty($id_service) || empty($date)) {
        header("Location: index.php?pesan=data_tidak_lengkap");
        exit();
    }

    $sql = "INSERT INTO bookings (user_id, id_service, booking_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        header("Location: index.php?pesan=gagal_booking");
        exit();
    }

    $stmt->bind_param("iis", $user_id, $id_service, $date);

    if ($stmt->execute()) {
        $_SESSION['booking_success'] = true;
        header("Location: index.php?pesan=berhasil_tambah");
        exit();
    } else {
        header("Location: index.php?pesan=gagal_booking");
        exit();
    }
}
?>
