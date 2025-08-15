<?php
// Mulai sesi
session_start();

// Sertakan file koneksi database
include 'koneksi.php';


// Periksa apakah form dikirim melalui metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan bersihkan data input
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validasi input
    if (empty($email) || empty($password)) {
        echo "<script>alert('Email dan password wajib diisi.'); window.location='index.php';</script>";
        exit();
    }

    // Siapkan query untuk ambil user berdasarkan email
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        die("Query gagal: " . mysqli_error($conn));
    }

    // Binding parameter
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Periksa apakah user ditemukan
    if ($data = mysqli_fetch_assoc($result)) {
        // Verifikasi password
        if (password_verify($password, $data['password'])) {
            // Set session

            $_SESSION['username_user'] = $data['username'];
            $_SESSION['user_id_user'] = $data['user_id'];
            $_SESSION['status_user'] = 'login';
            $_SESSION['role_user'] = $data['role']; // Pastikan nama kolom role di DB: 'user'
            // Arahkan pengguna berdasarkan peran (role)
            
            header("Location: index.php?pesan=berhasil_login");

            exit();
        } else {
            // Password tidak cocok
            echo "<script>alert('Login gagal! Email atau password salah.'); window.location='index.php';</script>";
            exit();
        }
    } else {
        // Email tidak ditemukan
        echo "<script>alert('Login gagal! Email atau password salah.'); window.location='index.php';</script>";
        exit();
    }
} else {
    // Akses langsung tanpa POST
    header("Location: index.php");
    exit();
}
?>
