<?php
session_start();
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_admin.php");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: login_admin.php?error=" . urlencode("Email tidak valid"));
    exit();
}

// Prepare statement
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
if (!$stmt) {
    // Debug error prepare statement, bisa di-log
    error_log("Prepare failed: " . $conn->error);
    header("Location: login_admin.php?error=" . urlencode("Terjadi kesalahan server"));
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($data = $result->fetch_assoc()) {
    if (password_verify($password, $data['password'])) {
        session_regenerate_id(true);

        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['user_id'] = $data['id'];

        header("Location: dashboard_admin.php");
        exit();
    } else {
        header("Location: login_admin.php?error=" . urlencode("Email atau password salah"));
        exit();
    }
} else {
    header("Location: login_admin.php?error=" . urlencode("Email atau password salah"));
    exit();
}
