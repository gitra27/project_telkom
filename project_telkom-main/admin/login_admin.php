<?php
/* =====================================================
   proses login admin
   ===================================================== */

session_start();
include "../config.php";

$error = false;

if (isset($_POST['login'])) {

    // ambil input
    $nama_admin     = $_POST['nama_admin'];
    $nik_admin      = $_POST['nik_admin'];
    $lantai_admin   = $_POST['lantai'];
    $password_admin = $_POST['password_admin'];

    // ambil data admin berdasarkan nik
    $query = mysqli_query($conn, "
        SELECT * FROM tb_admin
        WHERE nik_admin = '$nik_admin'
    ");

    if (mysqli_num_rows($query) === 1) {

        $data = mysqli_fetch_assoc($query);

        // validasi data
        if (
            $nama_admin === $data['nama_admin'] &&
            $lantai_admin === $data['lantai'] &&
            password_verify($password_admin, $data['password_admin'])
        ) {

            // simpan session admin (INI PENTING)
            $_SESSION['admin'] = [
                'nama_admin' => $data['nama_admin'],
                'nik_admin'  => $data['nik_admin'],
                'lantai'     => $data['lantai']
            ];

            header("location: dashboard_admin.php");
            exit;
        }
    }

    $error = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>login admin</title>
</head>
<body>

<div class="login-box">

    <h2>login admin</h2>

    <?php if ($error): ?>
        <div class="error">data admin tidak cocok</div>
    <?php endif; ?>

    <form method="post">

        <label>nama admin</label>
        <input type="text" name="nama_admin" required>

        <label>nik admin</label>
        <input type="text" name="nik_admin" required>

        <label>lantai admin</label>
        <select name="lantai" required>
            <option value="">- pilih lantai -</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="5">5</option>
            <option value="6">6</option>
        </select>

        <label>password admin</label>
        <input type="password" name="password_admin" required>

        <button type="submit" name="login">login</button>
    </form>

</div>

</body>
</html>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: arial, sans-serif;
}

body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-box {
    width: 420px;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 25px rgba(255,49,49,.35);
}

.login-box h2 {
    text-align: center;
    color: #ff3131;
    margin-bottom: 20px;
}

label {
    display: block;
    margin-top: 12px;
    font-weight: bold;
}

input, select {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border-radius: 5px;
    border: 1px solid #ccc;
}

button {
    width: 100%;
    padding: 12px;
    margin-top: 20px;
    background: #ff3131;
    border: none;
    color: white;
    font-weight: bold;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    background: #d62828;
}

.error {
    background: #ff3131;
    color: white;
    padding: 10px;
    text-align: center;
    border-radius: 5px;
    margin-bottom: 15px;
}
</style>
