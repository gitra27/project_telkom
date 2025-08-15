<?php
session_start();
require 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $password = $_POST["password"];

    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $data = mysqli_fetch_assoc($result);

        // Verifikasi password yang diinput dengan yang tersimpan (hash)
        if (password_verify($password, $data['password'])) {
            $_SESSION['user_id'] = $data['id']; 
            $_SESSION['role'] = $data['role'];

            if ($data['role'] == 'admin') {
                header("Location: dashboard_admin.php");
                exit();
            } elseif ($data['role'] == 'user') {
                header("Location: dashboard_user.php");
                exit();
            } else {
                echo "Role tidak dikenali.";
            }
        } else {
            echo "Password salah.";
        }
    } else {
        echo "Username tidak ditemukan.";
    }
}
?>
