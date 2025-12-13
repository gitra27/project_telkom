<?php
ob_start();
include "config.php";

if (isset($_POST['login'])) {

    $nama = trim($_POST['nama']);
    $nik  = trim($_POST['nik']);
    $pass = $_POST['password'];

    // CARI DATA
    $q = mysqli_query($conn, "SELECT * FROM tb_karyawan WHERE nama='$nama' AND nik='$nik'");
    $data = mysqli_fetch_assoc($q);

    // VALIDASI
    if (!$data) {
        $_SESSION['error'] = "Nama atau NIK salah!";
    } else if ($data['account_active'] == 0) {
        $_SESSION['error'] = "Akun kamu sudah tidak aktif.";
    } else if (!password_verify($pass, $data['password'])) {
        $_SESSION['error'] = "Password salah!";
    } else {

        // LOGIN SUKSES
        $_SESSION['nik']  = $data['nik'];
        $_SESSION['nama'] = $data['nama'];

        header("Location: dashboard.php");
        exit();
    }

    header("Location: login.php");
    exit();
}

ob_end_flush();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Sistem Absensi</title>
</head>
<body>

<?php if (isset($_SESSION['error'])): ?>
<script>
alert("<?= $_SESSION['error'] ?>");
</script>
<?php unset($_SESSION['error']); endif; ?>

<h2>Login</h2>

<form method="POST">
    <label>Nama</label><br>
    <input type="text" name="nama" required><br>

    <label>NIK</label><br>
    <input type="text" name="nik" required><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit" name="login">LOGIN</button>
</form>

</body>
</html>