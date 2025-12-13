<?php
include "../config.php";

if (isset($_POST['save'])) {

    $nama   = $_POST['nama_admin'];
    $nik    = $_POST['nik_admin'];
    $lantai = $_POST['lantai'];
    $pass   = password_hash($_POST['password_admin'], PASSWORD_DEFAULT);

    // CEK NIK SUDAH ADA
    $cek = mysqli_query($conn, "SELECT nik_admin FROM tb_admin WHERE nik_admin='$nik'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('NIK ADMIN sudah dipakai!'); window.location='tambah_admin.php';</script>";
        exit();
    }

    $q = mysqli_query($conn, "
        INSERT INTO tb_admin(nama_admin, nik_admin, password_admin, lantai)
        VALUES('$nama', '$nik', '$pass', '$lantai')
    ");

    if ($q) {
        echo "<script>alert('Admin berhasil ditambahkan!'); window.location='dashboard_superadmin.php?page=admin';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan admin!');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Admin</title>
    <style>
        body { font-family: Arial; background:#f5f5f5; padding:20px; }
        .box {
            width:420px; background:white; padding:20px;
            border-radius:10px; margin:auto;
        }
        input, select {
            width:100%; padding:10px; margin-top:8px;
        }
        button {
            width:100%; padding:12px; margin-top:15px;
            background:#ff3131; border:none; color:white;
            font-weight:bold; cursor:pointer; border-radius:5px;
        }
    </style>
</head>

<body>

<div class="box">
<h2>Tambah Admin</h2>

<form method="POST">

    <label>Nama Admin</label>
    <input type="text" name="nama_admin" required>

    <label>NIK Admin</label>
    <input type="text" name="nik_admin" required>

    <label>Lantai Admin</label>
    <select name="lantai" required>
        <option value="">- PILIH LANTAI -</option>
        <option>1</option>
        <option>2</option>
        <option>3</option>
        <option>5</option>
        <option>6</option>
    </select>

    <label>Password Admin</label>
    <input type="password" name="password_admin" required>

    <button type="submit" name="save">SIMPAN</button>

</form>
</div>

</body>
</html>
