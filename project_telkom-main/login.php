<?php
ob_start();
include "config.php";

if (isset($_POST['login'])) {

    $nama = trim($_POST['nama']);
    $nik  = trim($_POST['nik']);
    $pass = $_POST['password'];

    // Prevent SQL injection with prepared statements
    $stmt = mysqli_prepare($conn, "SELECT * FROM tb_karyawan WHERE nama = ? AND nik = ?");
    mysqli_stmt_bind_param($stmt, "ss", $nama, $nik);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    if (!$data) {
        $_SESSION['error'] = "Nama atau NIK salah!";
    } else if ($data['account_active'] == 0) {
        $_SESSION['error'] = "Akun kamu sudah tidak aktif.";
    } else if (!password_verify($pass, $data['password'])) {
        $_SESSION['error'] = "Password salah!";
    } else {
        $_SESSION['nik']  = $data['nik'];
        $_SESSION['nama'] = $data['nama'];

        header("Location: dashboard.php");
        exit();
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Sistem Absensi</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="login-box">
    <h2>Login Absensi</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <script>
            alert("<?= $_SESSION['error'] ?>");
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="POST">
        <label>Nama</label>
        <input type="text" name="nama" required>

        <label>NIK</label>
        <input type="text" name="nik" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" name="login">LOGIN</button>
    </form>

    <div class="footer-text">
        © Sistem Absensi
    </div>
</div>

</body>
</html>
