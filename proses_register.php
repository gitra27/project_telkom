<?php
session_start();
require 'koneksi.php';


// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "db_bengkel1");

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if ($email && $password) {
    // Cek apakah email sudah terdaftar
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Email sudah digunakan. Silakan pilih yang lain.";
    } else {
        // Hash password
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Simpan user baru
        $insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $insert->bind_param("sss", $username, $email, $hashed);

        if ($insert->execute()) {
            $_SESSION['username_user'] = $username;
            header("Location: index.php?pesan=berhasil_daftar"); // redirect ke homepage
            exit;
        } else {
            echo "Gagal mendaftar: " . $insert->error;
        }
    }
} else {
    echo "Harap isi semua data.";
}
?>
